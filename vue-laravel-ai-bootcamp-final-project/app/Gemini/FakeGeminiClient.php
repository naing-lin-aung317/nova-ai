<?php

namespace App\Gemini;

use App\Contracts\GeminiClient;
use App\Exceptions\GeminiStreamException;

class FakeGeminiClient implements GeminiClient
{
    /** @var list<string> */
    public static array $chunks = ['Hello', ' ', 'NOVA', ' ', 'AI'];

    public static bool $fail = false;

    public static string $failCode = 'provider_error';

    public static function reset(): void
    {
        self::$chunks = ['Hello', ' ', 'NOVA', ' ', 'AI'];
        self::$fail = false;
        self::$failCode = 'provider_error';
    }

    public function stream(string $systemInstruction, string $userPrompt): \Generator
    {
        yield from $this->streamOrFail($systemInstruction, $userPrompt);
    }

    public function streamOrFail(string $systemInstruction, string $userPrompt): \Generator
    {
        if (self::$fail) {
            throw new GeminiStreamException('Simulated provider failure.', self::$failCode);
        }

        foreach (self::$chunks as $chunk) {
            yield $chunk;
        }
    }
}
