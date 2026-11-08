<?php

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\CreditWallet;
use App\Models\User;
use App\Services\WalletService;

test('registration creates an empty wallet', function () {
    $this->post('/register', [
        'name' => 'Wallet User',
        'email' => 'wallet@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
    ]);

    $user = User::query()->where('email', 'wallet@example.com')->firstOrFail();

    expect($user->creditWallet)->not->toBeNull()
        ->and($user->creditWallet->available_balance)->toBe(0)
        ->and($user->creditWallet->transactions)->toHaveCount(0);
});

test('email verification grants the signup bonus once', function () {
    $user = User::factory()->unverified()->create();

    expect($user->creditWallet?->available_balance ?? 0)->toBe(0);

    $user->markEmailAsVerified();
    $user->refresh();

    expect($user->creditWallet?->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS);

    $user->markEmailAsVerified();
    $user->refresh();

    expect($user->creditWallet?->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS);

    expect(CreditTransaction::query()
        ->where('user_id', $user->id)
        ->where('type', CreditTransactionType::SignupBonus)
        ->count())->toBe(1);
});

test('wallet balance matches the ledger sum', function () {
    $user = User::factory()->create();
    $wallet = $user->creditWallet;

    expect($wallet)->not->toBeNull();

    expect(app(WalletService::class)->ledgerMatchesWallet($wallet))->toBeTrue();
});

test('deleting an account anonymises wallet and ledger rows', function () {
    $user = User::factory()->create();
    $walletId = $user->creditWallet?->id;

    expect($walletId)->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('settings.account.destroy'), [
            'password' => 'password',
        ])
        ->assertRedirect(route('home'));

    $wallet = CreditWallet::query()->findOrFail($walletId);

    expect($wallet->user_id)->toBeNull()
        ->and($wallet->anonymised_at)->not->toBeNull()
        ->and($wallet->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS);

    expect(CreditTransaction::query()->where('credit_wallet_id', $walletId)->count())->toBe(1);

    CreditTransaction::query()
        ->where('credit_wallet_id', $walletId)
        ->get()
        ->each(function (CreditTransaction $transaction): void {
            expect($transaction->user_id)->toBeNull()
                ->and($transaction->anonymised_at)->not->toBeNull();
        });
});

test('inertia shares wallet balances for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('wallet.available', WalletService::SIGNUP_BONUS_CREDITS)
            ->where('wallet.reserved', 0));
});
