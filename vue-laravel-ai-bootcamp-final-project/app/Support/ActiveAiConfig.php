<?php

namespace App\Support;

class ActiveAiConfig
{
    public static function model(): string
    {
        return (string) (config('nova.gemini.model') ?: config('ai.gemini.model') ?: 'unknown');
    }
}
