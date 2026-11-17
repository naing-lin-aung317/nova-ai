<?php

namespace Database\Factories;

use App\Enums\PaymentOrderStatus;
use App\Models\CreditPackage;
use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentOrder>
 */
class PaymentOrderFactory extends Factory
{
    protected $model = PaymentOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $package = CreditPackage::query()->first()
            ?? CreditPackage::factory()->create();

        return [
            'user_id' => User::factory(),
            'credit_package_id' => $package->id,
            'amount_cents' => $package->price_cents,
            'currency' => 'usd',
            'credits_to_issue' => $package->credits,
            'status' => PaymentOrderStatus::Pending,
            'stripe_checkout_session_id' => null,
            'stripe_payment_intent_id' => null,
            'credit_transaction_id' => null,
            'paid_at' => null,
        ];
    }
}
