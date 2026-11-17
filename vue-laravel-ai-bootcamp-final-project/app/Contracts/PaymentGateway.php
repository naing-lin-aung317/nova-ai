<?php

namespace App\Contracts;

use App\Models\CreditPackage;
use App\Models\PaymentOrder;

interface PaymentGateway
{
    /**
     * @return array{url: string, session_id: string|null}
     */
    public function createCheckoutSession(PaymentOrder $order, CreditPackage $package): array;
}
