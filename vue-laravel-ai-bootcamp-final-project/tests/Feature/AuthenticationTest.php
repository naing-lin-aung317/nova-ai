<?php

use App\Models\User;

test('users can authenticate and log out', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});

test('suspended users cannot log in', function () {
    $user = User::factory()->suspended()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('email verification activates pending users', function () {
    $user = User::factory()->unverified()->create();

    $user->markEmailAsVerified();

    $user->refresh();

    expect($user->status->value)->toBe('active');
});

test('users can request a password reset link', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password', [
        'email' => $user->email,
    ])->assertSessionHasNoErrors();
});
