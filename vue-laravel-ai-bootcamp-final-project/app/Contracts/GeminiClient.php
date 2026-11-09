<?php

namespace App\Contracts;

use App\Exceptions\GeminiStreamException;

interface GeminiClient
{
    /**
     * @return \Generator<string, void, void, void>
     */
    public function stream(string $systemInstruction, string $userPrompt): \Generator;

    /**
     * @throws GeminiStreamException
     */
    public function streamOrFail(string $systemInstruction, string $userPrompt): \Generator;
}
