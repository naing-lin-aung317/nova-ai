<?php

use App\Enums\CreditReservationStatus;
use App\Enums\GenerationStatus;
use App\Models\GenerationRequest;
use App\Models\User;
use App\Services\GenerationService;
use App\Services\WalletService;
use App\Support\AiHttpErrors;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

test('http 401 maps to invalid_api_key for user failure reason', function () {
    $body = '{"error":{"message":"API key not valid. Please pass a valid API key."}}';
    $response = new Response(new GuzzleHttp\Psr7\Response(401, [], $body));

    $exception = AiHttpErrors::fromResponse($response, 'gemini');

    expect(AiHttpErrors::userFailureReason($exception))->toBe('invalid_api_key');
});

test('failed generation stores friendly code and releases reservation without capture', function () {
    fakeGeminiGenerateContent(
        status: 401,
        body: ['error' => ['message' => 'API key not valid. Please pass a valid API key.']],
    );

    $user = User::factory()->create();

    $generationId = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'fail-invalid-key',
        ])
        ->json('id');

    $generation = GenerationRequest::query()->with('reservation')->findOrFail($generationId);

    app(GenerationService::class)->stream($generation);
    $generation->refresh();

    expect($generation->status)->toBe(GenerationStatus::Failed)
        ->and($generation->failure_reason)->toBe('invalid_api_key')
        ->and($generation->reservation?->status)->toBe(CreditReservationStatus::Released);

    $user->creditWallet?->refresh();

    expect($user->creditWallet?->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS);
});

test('region restriction stores friendly code on failed generation', function () {
    fakeGeminiGenerateContent(
        status: 400,
        body: [
            [
                'error' => [
                    'message' => 'User location is not supported for the API use.',
                    'status' => 'FAILED_PRECONDITION',
                ],
            ],
        ],
    );

    $user = User::factory()->create();

    $generationId = $this->actingAs($user)
        ->postJson('/generations', validGenerationPayload(), [
            'Idempotency-Key' => 'fail-region',
        ])
        ->json('id');

    $generation = GenerationRequest::query()->findOrFail($generationId);

    app(GenerationService::class)->stream($generation);
    $generation->refresh();

    expect($generation->failure_reason)->toBe('region_restricted');
});

test('friendly failure codes omit duplicate no-charge copy', function () {
    $messages = [
        'region_restricted' => 'The AI provider is not available in your region.',
        'missing_api_key' => 'The AI API key is not configured on the server.',
        'invalid_api_key' => 'The AI API key was rejected.',
        'missing_model' => 'The AI model is not configured on the server.',
        'unsupported_model' => 'That model is not available for generateContent.',
        'provider_error' => 'The AI provider could not complete this generation.',
        'generation_failed' => 'This generation could not be completed.',
        'timeout' => 'This generation timed out before the model responded.',
        'stopped' => 'Stopped.',
    ];

    foreach ($messages as $message) {
        expect(mb_strtolower($message))->not->toContain('no credit was charged');
    }
});
