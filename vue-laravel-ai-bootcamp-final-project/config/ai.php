<?php

return [
    'provider' => 'gemini',

    'timeout_seconds' => (int) env('AI_REQUEST_TIMEOUT', 60),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],
];
