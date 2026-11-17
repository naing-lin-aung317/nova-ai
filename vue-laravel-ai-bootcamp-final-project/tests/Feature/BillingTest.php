<?php

use App\Enums\PaymentOrderStatus;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\PaymentOrder;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\WalletService;
use Stripe\Event;

beforeEach(function (): void {
    config([
        'nova.stripe.fake' => true,
        'nova.stripe.webhook_secret' => 'whsec_test_secret',
    ]);
});

test('billing page lists packages and balance', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/billing')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('app/Billing')
            ->has('packages', 3)
            ->where('stripeFake', true));
});

test('fake checkout grants credits once', function () {
    $user = User::factory()->unverified()->create();
    app(WalletService::class)->ensureWallet($user);

    $package = CreditPackage::query()->where('slug', 'starter')->firstOrFail();

    $this->actingAs($user)
        ->post('/billing/checkout/'.$package->id)
        ->assertRedirect();

    $order = PaymentOrder::query()->where('user_id', $user->id)->latest()->firstOrFail();

    $this->actingAs($user)
        ->get(route('billing.fake-checkout', $order))
        ->assertRedirect(route('billing.success'));

    $order->refresh();
    expect($order->status)->toBe(PaymentOrderStatus::Paid);

    $user->creditWallet?->refresh();
    expect($user->creditWallet?->available_balance)->toBe(20);

    $this->actingAs($user)
        ->get(route('billing.fake-checkout', $order))
        ->assertRedirect(route('billing.success'));

    $user->creditWallet?->refresh();
    expect($user->creditWallet?->available_balance)->toBe(20);

    expect(CreditTransaction::query()->where('user_id', $user->id)->where('type', 'purchase')->count())->toBe(1);
});

test('duplicate stripe fulfillment does not double credit', function () {
    $user = User::factory()->unverified()->create();
    app(WalletService::class)->ensureWallet($user);

    $package = CreditPackage::query()->where('slug', 'creator')->firstOrFail();

    $order = PaymentOrder::query()->create([
        'user_id' => $user->id,
        'credit_package_id' => $package->id,
        'amount_cents' => $package->price_cents,
        'currency' => 'usd',
        'credits_to_issue' => $package->credits,
        'status' => PaymentOrderStatus::Pending,
    ]);

    $payments = app(PaymentService::class);
    $eventId = 'evt_test_dup';

    expect($payments->fulfillCheckoutSession(
        stripeEventId: $eventId,
        eventType: 'checkout.session.completed',
        sessionId: 'cs_test_'.$order->id,
        paymentIntentId: 'pi_test_'.$order->id,
        paymentOrderId: $order->id,
    ))->toBeTrue();

    expect($payments->fulfillCheckoutSession(
        stripeEventId: $eventId,
        eventType: 'checkout.session.completed',
        sessionId: 'cs_test_'.$order->id,
        paymentIntentId: 'pi_test_'.$order->id,
        paymentOrderId: $order->id,
    ))->toBeFalse();

    $user->creditWallet?->refresh();
    expect($user->creditWallet?->available_balance)->toBe(75);
});

test('refund webhook removes purchased credits', function () {
    $user = User::factory()->unverified()->create();
    app(WalletService::class)->ensureWallet($user);

    $package = CreditPackage::query()->where('slug', 'starter')->firstOrFail();

    $order = PaymentOrder::query()->create([
        'user_id' => $user->id,
        'credit_package_id' => $package->id,
        'amount_cents' => $package->price_cents,
        'currency' => 'usd',
        'credits_to_issue' => $package->credits,
        'status' => PaymentOrderStatus::Pending,
    ]);

    app(PaymentService::class)->fulfillCheckoutSession(
        stripeEventId: 'evt_refund_setup',
        eventType: 'checkout.session.completed',
        sessionId: 'cs_refund_setup',
        paymentIntentId: 'pi_refund_test',
        paymentOrderId: $order->id,
    );

    $order->refresh();

    $event = Event::constructFrom([
        'id' => 'evt_refund_1',
        'object' => 'event',
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_test',
                'payment_intent' => 'pi_refund_test',
            ],
        ],
    ]);

    app(PaymentService::class)->dispatchStripeEvent($event);

    $user->creditWallet?->refresh();
    expect($user->creditWallet?->available_balance)->toBe(0);

    $order->refresh();
    expect($order->status)->toBe(PaymentOrderStatus::Refunded);
});

test('stripe webhook rejects invalid signature when not in fake mode', function () {
    config(['nova.stripe.fake' => false]);

    $this->postJson('/webhooks/stripe', ['id' => 'evt_x'], [
        'Stripe-Signature' => 'bad_sig',
    ])->assertStatus(400);
});

test('unverified users can start checkout', function () {
    $user = User::factory()->unverified()->create();

    $package = CreditPackage::query()->where('slug', 'starter')->firstOrFail();

    $this->actingAs($user)
        ->post('/billing/checkout/'.$package->id)
        ->assertRedirect();
});

test('checkout creates a pending order and returns an inertia location for spa posts', function () {
    $user = User::factory()->create();
    $package = CreditPackage::query()->where('slug', 'starter')->firstOrFail();

    $response = $this->actingAs($user)
        ->withHeader('X-Inertia', 'true')
        ->post('/billing/checkout/'.$package->id)
        ->assertStatus(409);

    $order = PaymentOrder::query()->where('user_id', $user->id)->latest()->first();

    expect($order)->not->toBeNull()
        ->and($response->headers->get('X-Inertia-Location'))->toBe(route('billing.fake-checkout', [
            'paymentOrder' => $order->id,
        ]))
        ->and($order->status)->toBe(PaymentOrderStatus::Pending)
        ->and($order->credit_package_id)->toBe($package->id);
});
