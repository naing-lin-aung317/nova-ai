<?php

use App\Models\BuiltInTone;
use App\Models\CustomTone;
use App\Models\User;

test('tones page lists built-in and custom tones', function () {
    $user = User::factory()->create();
    CustomTone::factory()->for($user)->create(['name' => 'My brand voice']);

    $this->actingAs($user)
        ->get('/tones')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('app/Tones')
            ->has('builtInTones', BuiltInTone::query()->where('is_active', true)->count())
            ->has('customTones', 1));
});

test('users can create a custom tone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tones', [
            'name' => 'My brand voice',
            'description' => 'Short and honest.',
            'sample_content' => 'We ship every weekend.',
        ])
        ->assertRedirect(route('tones'));

    $this->assertDatabaseHas('custom_tones', [
        'user_id' => $user->id,
        'name' => 'My brand voice',
    ]);
});

test('users can update and delete their own custom tones', function () {
    $user = User::factory()->create();
    $tone = CustomTone::factory()->for($user)->create(['name' => 'Before']);

    $this->actingAs($user)
        ->put(route('tones.update', $tone), [
            'name' => 'After',
            'description' => 'Updated',
        ])
        ->assertRedirect(route('tones', ['edit' => $tone->id]));

    expect($tone->fresh()->name)->toBe('After');

    $this->actingAs($user)
        ->delete(route('tones.destroy', $tone))
        ->assertRedirect(route('tones'));

    $this->assertDatabaseMissing('custom_tones', ['id' => $tone->id]);
});

test('users cannot modify another users custom tone', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $tone = CustomTone::factory()->for($owner)->create();

    $this->actingAs($other)
        ->put(route('tones.update', $tone), [
            'name' => 'Hijacked',
            'description' => 'Nope',
        ])
        ->assertForbidden();

    $this->actingAs($other)
        ->delete(route('tones.destroy', $tone))
        ->assertForbidden();

    expect($tone->fresh()->name)->not->toBe('Hijacked');
});

test('account deletion removes custom tones', function () {
    $user = User::factory()->create();
    $toneId = CustomTone::factory()->for($user)->create()->id;

    $this->actingAs($user)
        ->delete(route('settings.account.destroy'), [
            'password' => 'password',
        ]);

    $this->assertDatabaseMissing('custom_tones', ['id' => $toneId]);
});
