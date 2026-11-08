<?php

namespace Database\Seeders;

use App\Models\BuiltInTone;
use Illuminate\Database\Seeder;

class BuiltInToneSeeder extends Seeder
{
    public function run(): void
    {
        if (BuiltInTone::query()->exists()) {
            return;
        }

        $tones = [
            ['name' => 'Friendly', 'description' => 'Warm, casual, easy to read', 'sort_order' => 1],
            ['name' => 'Professional', 'description' => 'Clear and confident', 'sort_order' => 2],
            ['name' => 'Playful', 'description' => 'Light, witty, upbeat', 'sort_order' => 3],
        ];

        foreach ($tones as $tone) {
            BuiltInTone::query()->create([
                ...$tone,
                'is_active' => true,
            ]);
        }
    }
}
