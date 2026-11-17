<?php

namespace App\Support;

class AiOutputChunker
{
    /**
     * @return \Generator<int, string, void, void>
     */
    public static function stream(string $text): \Generator
    {
        $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        foreach ($parts as $part) {
            if ($part !== '') {
                yield $part;
            }
        }
    }
}
