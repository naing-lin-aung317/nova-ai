<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

test('users can register with valid data', function () {
    Event::fake([Registered::class]);

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

    Event::assertDispatched(Registered::class);
});

test('registration requires accepting terms', function () {
    $this->post('/register', [
        'name' => 'Aye Chan',
        'email' => 'aye2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('terms');
});
