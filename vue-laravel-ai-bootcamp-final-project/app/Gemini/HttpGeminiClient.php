<?php

namespace App\Gemini;

use App\Contracts\GeminiClient;
use App\Exceptions\GeminiStreamException;
use Illuminate\Support\Facades\Http;

class HttpGeminiClient implements GeminiClient
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
        private readonly int $timeoutSeconds,
    ) {}

    public function stream(string $systemInstruction, string $userPrompt): \Generator
    {
        return $this->streamOrFail($systemInstruction, $userPrompt);
    }

    public function streamOrFail(string $systemInstruction, string $userPrompt): \Generator
    {
        if ($this->apiKey === null || $this->apiKey === '') {
            throw new GeminiStreamException('Gemini API key is not configured.', 'missing_api_key');
        }

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:streamGenerateContent?key=%s',
            $this->model,
            $this->apiKey,
        );

        $response = Http::timeout($this->timeoutSeconds)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, [
                'system_instruction' => [
                    'parts' => [['text' => $systemInstruction]],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => $userPrompt]],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            throw new GeminiStreamException(
                'Gemini returned an error response.',
                'provider_error',
            );
        }

        $body = $response->body();
        $lines = preg_split('/\r\n|\n|\r/', $body) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (! is_array($decoded)) {
                continue;
            }

            $parts = data_get($decoded, 'candidates.0.content.parts', []);

            if (! is_array($parts)) {
                continue;
            }

            $chunk = '';

            foreach ($parts as $part) {
                if (! is_array($part)) {
                    continue;
                }

                $text = $part['text'] ?? null;

                if (is_string($text) && $text !== '') {
                    $chunk .= $text;
                }
            }

            if ($chunk !== '') {
                yield $chunk;
            }
        }
    }
}
