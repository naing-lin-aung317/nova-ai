<?php

use App\Enums\GenerationStatus;
use App\Models\GenerationRequest;
use App\Models\User;
use App\Services\WalletService;
use Inertia\Testing\AssertableInertia as Assert;

test('history list shows only the current users generations', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    GenerationRequest::factory()->for($user)->count(2)->completed()->create();
    GenerationRequest::factory()->for($other)->completed()->create();

    $this->actingAs($user)
        ->get('/history')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/History')
            ->has('generations.data', 2));
});

test('history search filters by topic', function () {
    $user = User::factory()->create();

    GenerationRequest::factory()->for($user)->completed()->create(['topic' => 'Autumn candles']);
    GenerationRequest::factory()->for($user)->completed()->create(['topic' => 'Summer swimwear']);

    $this->actingAs($user)
        ->get('/history?q=candles')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('generations.data', 1)
            ->where('generations.data.0.topic', 'Autumn candles'));
});

test('history detail is owner only', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $generation = GenerationRequest::factory()->for($owner)->completed()->create();

    $this->actingAs($other)
        ->get(route('history.show', $generation))
        ->assertForbidden();
});

test('patch output on completed generation does not change wallet', function () {
    $user = User::factory()->create();
    $generation = GenerationRequest::factory()->for($user)->completed()->create([
        'output_text' => 'Before',
    ]);

    $before = $user->creditWallet?->available_balance;

    $this->actingAs($user)
        ->patchJson(route('generations.update', $generation), [
            'output_text' => 'After edit',
        ])
        ->assertOk()
        ->assertJsonPath('output_text', 'After edit');

    $user->creditWallet?->refresh();

    expect($user->creditWallet?->available_balance)->toBe($before);
});

test('patch rejects non completed generations', function () {
    $user = User::factory()->create();

    $generation = GenerationRequest::factory()->for($user)->create([
        'status' => GenerationStatus::Failed,
        'output_text' => null,
    ]);

    $this->actingAs($user)
        ->patchJson(route('generations.update', $generation), [
            'output_text' => 'Nope',
        ])
        ->assertStatus(422);
});

test('delete removes a completed generation', function () {
    $user = User::factory()->create();
    $generation = GenerationRequest::factory()->for($user)->completed()->create();

    $this->actingAs($user)
        ->delete(route('generations.destroy', $generation))
        ->assertRedirect(route('history'));

    expect(GenerationRequest::query()->find($generation->id))->toBeNull();
});

test('delete rejects an active generation', function () {
    $user = User::factory()->create();

    $generation = GenerationRequest::factory()->for($user)->create([
        'status' => GenerationStatus::Pending,
        'output_text' => null,
        'completed_at' => null,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('generations.destroy', $generation))
        ->assertStatus(409);
});

test('dashboard includes recent completed content', function () {
    $user = User::factory()->create();

    GenerationRequest::factory()->for($user)->completed()->create([
        'topic' => 'Dashboard item',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('stats.generationsTotal', 1)
            ->has('recentContent', 1)
            ->where('recentContent.0.topic', 'Dashboard item'));
});

test('authenticated users can open generator and history show', function () {
    $user = User::factory()->create();
    $generation = GenerationRequest::factory()->for($user)->completed()->create();

    $this->actingAs($user)
        ->get('/generator')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('app/Generator'));

    $this->actingAs($user)
        ->get(route('history.show', $generation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/HistoryShow')
            ->where('generation.id', $generation->id));
});

test('wallet balance unchanged after free edit', function () {
    $user = User::factory()->create();
    $generation = GenerationRequest::factory()->for($user)->completed()->create();

    $expected = $user->creditWallet?->available_balance ?? WalletService::SIGNUP_BONUS_CREDITS;

    $this->actingAs($user)
        ->patchJson(route('generations.update', $generation), [
            'output_text' => str_repeat('x', 50),
        ])
        ->assertOk();

    $user->creditWallet?->refresh();

    expect($user->creditWallet?->available_balance)->toBe($expected);
});
