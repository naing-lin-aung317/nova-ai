<?php

use App\Models\User;

test('users can update profile information', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/user/profile-information', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('users can update preferences', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('settings.preferences.update'), [
            'theme' => 'dark',
            'preferred_language' => 'my',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->theme)->toBe('dark');
    expect($user->preferred_language)->toBe('my');
});

test('users can delete their account with password confirmation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.account.destroy'), [
            'password' => 'password',
        ])
        ->assertRedirect(route('home'));

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertGuest();
});

test('account deletion requires the correct password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings')
        ->delete(route('settings.account.destroy'), [
            'password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('password');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
