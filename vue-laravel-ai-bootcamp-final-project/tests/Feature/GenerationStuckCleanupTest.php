<?php

use App\Enums\GenerationStatus;
use App\Models\GenerationRequest;
use App\Models\User;
use App\Services\GenerationService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Http;

test('stuck pending generation is failed and releases credits', function () {
    config(['nova.generation.stuck_timeout_minutes' => 2]);

    $user = User::factory()->create();

    $generationId = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'stuck-pending',
        ])
        ->json('id');

    $generation = GenerationRequest::query()->findOrFail($generationId);
    $generation->forceFill([
        'created_at' => now()->subMinutes(3),
        'updated_at' => now()->subMinutes(3),
    ])->save();

    $count = app(GenerationService::class)->releaseStuckGenerations($user->id);

    expect($count)->toBe(1);

    $generation->refresh();

    expect($generation->status)->toBe(GenerationStatus::Failed)
        ->and($generation->failure_reason)->toBe('timeout');

    $user->creditWallet?->refresh();

    expect($user->creditWallet?->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS);
});

test('concurrency limit includes active generation id', function () {
    Http::preventStrayRequests();
    configureGeminiForTests();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'active-one',
        ])
        ->assertCreated();

    $activeId = GenerationRequest::query()
        ->where('user_id', $user->id)
        ->whereIn('status', [GenerationStatus::Pending, GenerationStatus::Streaming])
        ->value('id');

    $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'active-two',
        ])
        ->assertStatus(429)
        ->assertJsonPath('code', 'concurrency_limit')
        ->assertJsonPath('active_generation_id', $activeId);
});
