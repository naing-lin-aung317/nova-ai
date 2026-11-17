<?php

namespace Database\Factories;

use App\Models\CreditPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditPackage>
 */
class CreditPackageFactory extends Factory
{
    protected $model = CreditPackage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Starter',
            'slug' => 'starter',
            'credits' => 20,
            'price_cents' => 1000,
            'currency' => 'usd',
            'is_visible' => true,
            'sort_order' => 1,
        ];
    }
}
