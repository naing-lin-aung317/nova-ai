<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

class CreditPackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'credits' => 20,
                'price_cents' => 1000,
                'sort_order' => 1,
            ],
            [
                'name' => 'Creator',
                'slug' => 'creator',
                'credits' => 75,
                'price_cents' => 3000,
                'sort_order' => 2,
            ],
            [
                'name' => 'Agency',
                'slug' => 'agency',
                'credits' => 300,
                'price_cents' => 10000,
                'sort_order' => 3,
            ],
        ];

        foreach ($packages as $package) {
            CreditPackage::query()->updateOrCreate(
                ['slug' => $package['slug']],
                [
                    ...$package,
                    'currency' => 'usd',
                    'is_visible' => true,
                ],
            );
        }
    }
}
