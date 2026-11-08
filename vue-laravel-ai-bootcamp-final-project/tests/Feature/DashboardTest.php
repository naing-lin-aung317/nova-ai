<?php

use App\Models\CustomTone;
use App\Models\User;
use App\Services\WalletService;
use Inertia\Testing\AssertableInertia as Assert;

test('dashboard shows wallet stats and signup bonus activity', function () {
    $user = User::factory()->create();
    CustomTone::factory()->for($user)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/Dashboard')
            ->where('stats.availableCredits', WalletService::SIGNUP_BONUS_CREDITS)
            ->where('stats.generations', 0)
            ->where('stats.customTones', 1)
            ->has('recentTransactions', 1)
            ->where('recentContent', []));
});

test('dashboard flags low credits at three or fewer', function () {
    $user = User::factory()->create();
    $wallet = $user->creditWallet;
    $wallet->update(['available_balance' => 3]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('lowCredits', true));
});
