<?php

use App\Models\BuiltInTone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

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

function configureGeminiForTests(): void
{
    config([
        'ai.gemini.api_key' => 'test-gemini-key',
        'ai.gemini.model' => 'gemini-test',
        'ai.gemini.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'ai.timeout_seconds' => 60,
    ]);
}

/**
 * @param  array<string, mixed>|string  $body
 */
function fakeGeminiGenerateContent(
    string $text = 'Hello NOVA AI',
    int $status = 200,
    array|string $body = [],
): void {
    configureGeminiForTests();

    Http::fake(function (Request $request) use ($text, $status, $body) {
        if (! str_contains($request->url(), ':generateContent')) {
            return Http::response(['error' => ['message' => 'unexpected url']], 500);
        }

        if ($status !== 200) {
            $payload = is_array($body) && $body !== [] ? $body : (is_string($body) && $body !== '' ? json_decode($body, true) : ['error' => ['message' => 'error']]);

            return Http::response($payload, $status);
        }

        return Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => $text],
                        ],
                    ],
                ],
            ],
        ], 200);
    });
}
