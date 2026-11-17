<?php

namespace App\Gemini;

use App\Contracts\AiProvider;
use App\Contracts\GeminiClient;
use App\Support\AiOutputChunker;
use App\Support\AiPrompt;

/**
 * @deprecated Use {@see AiProvider} directly. Kept only if legacy code still type-hints GeminiClient.
 */
class GeminiClientAdapter implements GeminiClient
{
    public function __construct(
        private readonly AiProvider $ai,
    ) {}

    public function stream(string $systemInstruction, string $userPrompt): \Generator
    {
        yield from $this->streamOrFail($systemInstruction, $userPrompt);
    }

    public function streamOrFail(string $systemInstruction, string $userPrompt): \Generator
    {
        $text = $this->ai->generate(AiPrompt::encode($systemInstruction, $userPrompt));

        yield from AiOutputChunker::stream($text);
    }
}
