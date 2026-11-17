<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('NOVA_ADMIN_EMAIL');

        if (! is_string($email) || $email === '') {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'NOVA Admin',
                'password' => Hash::make(env('NOVA_ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
                'role' => UserRole::SuperAdmin,
                'status' => UserStatus::Active,
                'preferred_language' => 'en',
                'theme' => 'light',
            ],
        );
    }
}
