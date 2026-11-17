<?php

namespace App\Services\Ai;

use App\Contracts\AiProvider;
use App\Exceptions\AiProviderException;
use App\Support\AiHttpErrors;
use App\Support\AiPrompt;
use Illuminate\Support\Facades\Http;

class GeminiProvider implements AiProvider
{
    public function generate(string $prompt): string
    {
        $apiKey = config('ai.gemini.api_key');
        $model = config('ai.gemini.model');
        $baseUrl = (string) config('ai.gemini.base_url');
        $timeout = (int) config('ai.timeout_seconds', 60);

        if (! is_string($apiKey) || $apiKey === '') {
            throw new AiProviderException(
                'missing_api_key',
                'missing_api_key',
            );
        }

        if (! is_string($model) || $model === '') {
            throw new AiProviderException(
                'missing_model',
                'missing_model',
            );
        }

        $parts = AiPrompt::decode($prompt);

        $url = sprintf('%s/models/%s:generateContent', $baseUrl, $model);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
            ->post($url, [
                'system_instruction' => [
                    'parts' => [['text' => $parts['system']]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $parts['user']]],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw AiHttpErrors::fromResponse($response, 'gemini');
        }

        $text = $this->extractText($response->json());

        if ($text === '') {
            throw new AiProviderException(
                'The AI provider returned an empty response.',
                'empty_response',
                $response->status(),
            );
        }

        return $text;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    protected function extractText(?array $payload): string
    {
        if ($payload === null) {
            return '';
        }

        $parts = data_get($payload, 'candidates.0.content.parts', []);

        if (! is_array($parts)) {
            return '';
        }

        $buffer = '';

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }

            $text = $part['text'] ?? null;

            if (is_string($text) && $text !== '') {
                $buffer .= $text;
            }
        }

        return $buffer;
    }
}
