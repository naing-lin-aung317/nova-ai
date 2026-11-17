<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Models\CreditPackage;
use App\Models\PaymentOrder;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripePaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $secretKey,
    ) {
        Stripe::setApiKey($this->secretKey);
    }

    public function createCheckoutSession(PaymentOrder $order, CreditPackage $package): array
    {
        $session = Session::create([
            'mode' => 'payment',
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $package->currency,
                        'unit_amount' => $package->price_cents,
                        'product_data' => [
                            'name' => $package->name.' — '.$package->credits.' credits',
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'client_reference_id' => (string) $order->id,
            'metadata' => [
                'payment_order_id' => (string) $order->id,
            ],
            'success_url' => route('billing.success', [], true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.cancel', [], true),
        ]);

        $url = $session->url ?? throw new \RuntimeException('Stripe did not return a checkout URL.');

        return [
            'url' => $url,
            'session_id' => $session->id,
        ];
    }
}
