<?php

use App\Enums\GenerationStatus;
use App\Gemini\FakeGeminiClient;
use App\Models\BuiltInTone;
use App\Models\GenerationRequest;
use App\Models\User;
use App\Services\GenerationService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Artisan;

/**
 * @return array<string, mixed>
 */
function validGenerationPayload(): array
{
    return [
        'content_type' => 'facebook_post',
        'language' => 'en',
        'length' => 'medium',
        'built_in_tone_id' => BuiltInTone::query()->value('id'),
        'topic' => 'Handmade candles for autumn evenings',
    ];
}

beforeEach(function (): void {
    FakeGeminiClient::reset();
});

test('refuses generation when no credits are available', function () {
    $user = User::factory()->create();
    $user->creditWallet?->update(['available_balance' => 0]);

    $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload())
        ->assertStatus(422)
        ->assertJsonPath('code', 'insufficient_credits');
});

test('successful stream captures one credit', function () {
    $user = User::factory()->create();

    $create = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'stream-success',
        ])
        ->assertCreated();

    $generationId = $create->json('id');

    $generation = GenerationRequest::query()->findOrFail($generationId);

    app(GenerationService::class)->stream($generation);
    $generation->refresh();

    expect($generation->status)->toBe(GenerationStatus::Completed)
        ->and($generation->output_text)->toBe('Hello NOVA AI');

    $user->creditWallet?->refresh();

    expect($user->creditWallet?->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS - 1)
        ->and($user->creditWallet?->reserved_balance)->toBe(0);
});

test('provider failure releases the reservation without charging', function () {
    FakeGeminiClient::$fail = true;

    $user = User::factory()->create();

    $generationId = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'stream-fail',
        ])
        ->json('id');

    $generation = GenerationRequest::query()->findOrFail($generationId);

    app(GenerationService::class)->stream($generation);
    $generation->refresh();

    expect($generation->status)->toBe(GenerationStatus::Failed);

    $user->creditWallet?->refresh();

    expect($user->creditWallet?->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS);
});

test('duplicate idempotency key returns the same generation', function () {
    $user = User::factory()->create();

    $first = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'same-key',
        ])
        ->assertCreated()
        ->json('id');

    $second = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'same-key',
        ])
        ->assertCreated()
        ->json('id');

    expect($second)->toBe($first);

    expect(GenerationRequest::query()->count())->toBe(1);
});

test('blocks a second concurrent generation for the same user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'active-one',
        ])
        ->assertCreated();

    $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'active-two',
        ])
        ->assertStatus(429)
        ->assertJsonPath('code', 'concurrency_limit');
});

test('cancel before streaming releases the reservation', function () {
    $user = User::factory()->create();

    $generationId = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'cancel-me',
        ])
        ->json('id');

    $this->actingAs($user)
        ->postJson("/generations/{$generationId}/cancel")
        ->assertOk();

    $generation = GenerationRequest::query()->findOrFail($generationId);

    expect($generation->status)->toBe(GenerationStatus::Stopped);

    $user->creditWallet?->refresh();

    expect($user->creditWallet?->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS);
});

test('another user cannot view a generation', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $generationId = $this->actingAs($owner)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'owner-only',
        ])
        ->json('id');

    $this->actingAs($other)
        ->getJson("/generations/{$generationId}")
        ->assertForbidden();
});

test('stream route responds for the generation owner', function () {
    $user = User::factory()->create();

    $generationId = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'stream-route',
        ])
        ->json('id');

    $this->actingAs($user)
        ->get("/generations/{$generationId}/stream")
        ->assertOk()
        ->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
});

test('stale reservation cleanup releases credits', function () {
    $user = User::factory()->create();

    $generationId = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'stale',
        ])
        ->json('id');

    $generation = GenerationRequest::query()->findOrFail($generationId);
    $generation->reservation?->update(['expires_at' => now()->subMinute()]);

    Artisan::call('nova:release-stale-reservations');

    $generation->refresh();
    $user->creditWallet?->refresh();

    expect($generation->status)->toBe(GenerationStatus::Failed)
        ->and($user->creditWallet?->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS)
        ->and($user->creditWallet?->reserved_balance)->toBe(0);
});
