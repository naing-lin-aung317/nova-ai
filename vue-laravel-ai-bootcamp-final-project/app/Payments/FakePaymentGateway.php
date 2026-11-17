<?php

namespace App\Payments;

use App\Contracts\PaymentGateway;
use App\Models\CreditPackage;
use App\Models\PaymentOrder;

class FakePaymentGateway implements PaymentGateway
{
    public function createCheckoutSession(PaymentOrder $order, CreditPackage $package): array
    {
        return [
            'url' => route('billing.fake-checkout', [
                'paymentOrder' => $order->id,
            ]),
            'session_id' => null,
        ];
    }
}
