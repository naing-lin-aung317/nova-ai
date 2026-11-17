<?php

use App\Contracts\AiProvider;
use App\Exceptions\AiProviderException;
use App\Services\Ai\GeminiProvider;
use App\Support\AiPrompt;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('container always resolves gemini provider', function () {
    expect(app(AiProvider::class))->toBeInstanceOf(GeminiProvider::class);
});

test('gemini provider throws when api key is missing', function () {
    config([
        'ai.gemini.api_key' => null,
        'ai.gemini.model' => 'test-model',
        'ai.timeout_seconds' => 60,
    ]);

    $provider = new GeminiProvider;

    try {
        $provider->generate(AiPrompt::encode('System', 'User'));
        expect()->fail('Expected AiProviderException was not thrown.');
    } catch (AiProviderException $exception) {
        expect($exception->errorCode)->toBe('missing_api_key');
    }
});

test('gemini provider calls generateContent with x-goog-api-key header', function () {
    fakeGeminiGenerateContent('Test output');

    $provider = app(GeminiProvider::class);
    $text = $provider->generate(AiPrompt::encode('System rules', 'Write a short greeting'));

    expect($text)->toBe('Test output');

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), '/models/gemini-test:generateContent')
            && $request->hasHeader('x-goog-api-key', 'test-gemini-key');
    });
});
