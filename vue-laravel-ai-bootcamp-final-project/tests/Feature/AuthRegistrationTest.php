<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

test('users can register with valid data', function () {
    $response = $this->post('/register', [
        'name' => 'Aye Chan',
        'email' => 'aye@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'preferred_language' => 'en',
        'theme' => 'light',
    ]);

    $response->assertRedirect(route('verification.notice'));

    $this->assertDatabaseHas('users', [
        'email' => 'aye@example.com',
        'role' => UserRole::User->value,
        'status' => UserStatus::PendingVerification->value,
        'preferred_language' => 'en',
        'theme' => 'light',
    ]);

    $user = User::query()->where('email', 'aye@example.com')->firstOrFail();

    expect($user->creditWallet)->not->toBeNull()
        ->and($user->creditWallet->available_balance)->toBe(0);
});

test('registration requires accepting terms', function () {
    $this->post('/register', [
        'name' => 'Aye Chan',
        'email' => 'aye2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('terms');
});
