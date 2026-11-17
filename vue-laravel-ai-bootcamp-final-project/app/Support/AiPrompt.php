<?php

namespace App\Support;

use InvalidArgumentException;

class AiPrompt
{
    /**
     * @return non-empty-string
     */
    public static function encode(string $systemInstruction, string $userPrompt): string
    {
        return json_encode([
            'system' => $systemInstruction,
            'user' => $userPrompt,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{system: string, user: string}
     */
    public static function decode(string $prompt): array
    {
        $decoded = json_decode($prompt, true);

        if (! is_array($decoded) || ! isset($decoded['system'], $decoded['user'])) {
            throw new InvalidArgumentException('AI prompt payload is invalid.');
        }

        return [
            'system' => (string) $decoded['system'],
            'user' => (string) $decoded['user'],
        ];
    }
}
