<?php

namespace Database\Factories;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\CreditWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'credit_wallet_id' => CreditWallet::factory(),
            'user_id' => null,
            'type' => CreditTransactionType::SignupBonus,
            'amount' => 20,
            'available_balance_after' => 20,
            'reserved_balance_after' => 0,
            'description' => 'Signup bonus',
        ];
    }
}
