<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Enums\CreditTransactionType;
use App\Enums\PaymentOrderStatus;
use App\Models\CreditPackage;
use App\Models\PaymentOrder;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class PaymentService
{
    public function __construct(
        public PaymentGateway $gateway,
        public WalletService $wallets,
        public ?string $webhookSecret,
    ) {}

    public function beginCheckout(User $user, CreditPackage $package): string
    {
        abort_unless($package->is_visible, 404);

        $order = PaymentOrder::query()->create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'amount_cents' => $package->price_cents,
            'currency' => $package->currency,
            'credits_to_issue' => $package->credits,
            'status' => PaymentOrderStatus::Pending,
        ]);

        $checkout = $this->gateway->createCheckoutSession($order, $package);

        if ($checkout['session_id'] !== null) {
            $order->forceFill([
                'stripe_checkout_session_id' => $checkout['session_id'],
            ])->save();
        }

        return $checkout['url'];
    }

    public function fulfillCheckoutSession(
        string $stripeEventId,
        string $eventType,
        string $sessionId,
        ?string $paymentIntentId,
        int $paymentOrderId,
        ?array $payload = null,
    ): bool {
        if ($this->eventAlreadyProcessed($stripeEventId)) {
            return false;
        }

        return DB::transaction(function () use (
            $stripeEventId,
            $eventType,
            $sessionId,
            $paymentIntentId,
            $paymentOrderId,
            $payload,
        ): bool {
            $order = PaymentOrder::query()->whereKey($paymentOrderId)->lockForUpdate()->first();

            if ($order === null) {
                $this->recordWebhookEvent($stripeEventId, $eventType, null, $payload);

                return false;
            }

            if ($order->status === PaymentOrderStatus::Paid) {
                $this->recordWebhookEvent($stripeEventId, $eventType, $order->id, $payload);

                return false;
            }

            if ($order->status !== PaymentOrderStatus::Pending) {
                $this->recordWebhookEvent($stripeEventId, $eventType, $order->id, $payload);

                return false;
            }

            $transaction = $this->wallets->grantPurchase($order);

            $order->forceFill([
                'status' => PaymentOrderStatus::Paid,
                'stripe_checkout_session_id' => $sessionId,
                'stripe_payment_intent_id' => $paymentIntentId,
                'credit_transaction_id' => $transaction->id,
                'paid_at' => now(),
            ])->save();

            $this->recordWebhookEvent($stripeEventId, $eventType, $order->id, $payload);

            return true;
        });
    }

    public function fulfillManualByAdmin(PaymentOrder $order, User $admin): bool
    {
        if ($order->status !== PaymentOrderStatus::Pending) {
            throw new \InvalidArgumentException('Only pending orders can be verified manually.');
        }

        $fulfilled = $this->fulfillCheckoutSession(
            stripeEventId: 'admin:manual:'.$order->id,
            eventType: 'checkout.session.completed',
            sessionId: $order->stripe_checkout_session_id ?? 'admin_manual_'.$order->id,
            paymentIntentId: $order->stripe_payment_intent_id ?? 'admin_pi_'.$order->id,
            paymentOrderId: $order->id,
            payload: ['manual' => true, 'admin_user_id' => $admin->id],
        );

        if ($fulfilled) {
            $order->refresh();
            $order->forceFill([
                'fulfilled_by_admin_at' => now(),
                'fulfilled_by_user_id' => $admin->id,
            ])->save();
        }

        return $fulfilled;
    }

    public function fulfillFakeCheckout(PaymentOrder $order): bool
    {
        $eventId = 'fake:checkout.session.completed:'.$order->id;

        return $this->fulfillCheckoutSession(
            stripeEventId: $eventId,
            eventType: 'checkout.session.completed',
            sessionId: 'fake_cs_'.$order->id,
            paymentIntentId: 'fake_pi_'.$order->id,
            paymentOrderId: $order->id,
            payload: ['fake' => true],
        );
    }

    public function handleStripeWebhook(string $payload, ?string $signatureHeader): void
    {
        if ($this->webhookSecret === null || $this->webhookSecret === '') {
            abort(503, 'Stripe webhook secret is not configured.');
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            abort(400, 'Missing Stripe signature.');
        }

        try {
            $event = Webhook::constructEvent($payload, $signatureHeader, $this->webhookSecret);
        } catch (SignatureVerificationException) {
            abort(400, 'Invalid Stripe signature.');
        }

        $this->dispatchStripeEvent($event);
    }

    public function dispatchStripeEvent(Event $event): void
    {
        $object = $event->data->object ?? null;

        if (! is_object($object)) {
            return;
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted(
                $event->id,
                $event->type,
                $object,
            ),
            'charge.refunded' => $this->handleChargeRefunded($event->id, $event->type, $object),
            'charge.dispute.created' => $this->handleChargeDisputeCreated($event->id, $event->type, $object),
            default => null,
        };
    }

    protected function handleCheckoutSessionCompleted(
        string $eventId,
        string $eventType,
        object $session,
    ): void {
        $orderId = (int) ($session->metadata->payment_order_id ?? $session->client_reference_id ?? 0);

        if ($orderId <= 0) {
            $this->recordWebhookEvent($eventId, $eventType, null, json_decode(json_encode($session), true));

            return;
        }

        $this->fulfillCheckoutSession(
            stripeEventId: $eventId,
            eventType: $eventType,
            sessionId: (string) ($session->id ?? ''),
            paymentIntentId: is_string($session->payment_intent ?? null) ? $session->payment_intent : null,
            paymentOrderId: $orderId,
            payload: json_decode(json_encode($session), true),
        );
    }

    protected function handleChargeRefunded(string $eventId, string $eventType, object $charge): void
    {
        if ($this->eventAlreadyProcessed($eventId)) {
            return;
        }

        $paymentIntentId = is_string($charge->payment_intent ?? null) ? $charge->payment_intent : null;

        if ($paymentIntentId === null) {
            $this->recordWebhookEvent($eventId, $eventType, null, json_decode(json_encode($charge), true));

            return;
        }

        DB::transaction(function () use ($eventId, $eventType, $paymentIntentId, $charge): void {
            $order = PaymentOrder::query()
                ->where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            if ($order === null || $order->status !== PaymentOrderStatus::Paid) {
                $this->recordWebhookEvent($eventId, $eventType, $order?->id, json_decode(json_encode($charge), true));

                return;
            }

            $this->wallets->clawBackPurchase(
                $order,
                CreditTransactionType::Refund,
                'Purchase refunded',
            );

            $order->forceFill(['status' => PaymentOrderStatus::Refunded])->save();

            $this->recordWebhookEvent($eventId, $eventType, $order->id, json_decode(json_encode($charge), true));
        });
    }

    protected function handleChargeDisputeCreated(string $eventId, string $eventType, object $dispute): void
    {
        if ($this->eventAlreadyProcessed($eventId)) {
            return;
        }

        $paymentIntentId = is_string($dispute->payment_intent ?? null) ? $dispute->payment_intent : null;

        if ($paymentIntentId === null) {
            $this->recordWebhookEvent($eventId, $eventType, null, json_decode(json_encode($dispute), true));

            return;
        }

        DB::transaction(function () use ($eventId, $eventType, $paymentIntentId, $dispute): void {
            $order = PaymentOrder::query()
                ->where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                $this->recordWebhookEvent($eventId, $eventType, null, json_decode(json_encode($dispute), true));

                return;
            }

            if ($order->status === PaymentOrderStatus::Paid) {
                $this->wallets->clawBackPurchase(
                    $order,
                    CreditTransactionType::Chargeback,
                    'Payment disputed',
                );
            }

            $order->forceFill(['status' => PaymentOrderStatus::Disputed])->save();

            $this->recordWebhookEvent($eventId, $eventType, $order->id, json_decode(json_encode($dispute), true));
        });
    }

    protected function eventAlreadyProcessed(string $stripeEventId): bool
    {
        return PaymentWebhookEvent::query()
            ->where('stripe_event_id', $stripeEventId)
            ->exists();
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    protected function recordWebhookEvent(
        string $stripeEventId,
        string $eventType,
        ?int $paymentOrderId,
        ?array $payload,
    ): void {
        PaymentWebhookEvent::query()->create([
            'stripe_event_id' => $stripeEventId,
            'event_type' => $eventType,
            'payment_order_id' => $paymentOrderId,
            'payload' => $payload,
            'processed_at' => now(),
        ]);
    }
}
