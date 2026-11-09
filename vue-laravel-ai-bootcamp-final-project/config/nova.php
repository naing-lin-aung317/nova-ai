<?php

return [
    'gemini' => [
        'fake' => (bool) env('GEMINI_FAKE', false),
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'request_timeout_seconds' => (int) env('GEMINI_REQUEST_TIMEOUT', 120),
        'reservation_timeout_minutes' => (int) env('GEMINI_RESERVATION_TIMEOUT_MINUTES', 5),
    ],
    'generation' => [
        'max_concurrent_per_user' => (int) env('GENERATION_MAX_CONCURRENT', 1),
        'topic_max_length' => 1000,
        'output_max_length' => 50000,
    ],
    'content_length_ranges' => [
        'default' => [
            'short' => 'under 150 words',
            'medium' => '150-300 words',
            'long' => '300+ words',
        ],
        'tiktok_script' => [
            'short' => 'under 80 words',
            'medium' => '80-150 words',
            'long' => '150-250 words',
        ],
        'social_caption' => [
            'short' => 'under 40 words',
            'medium' => '40-80 words',
            'long' => '80-120 words',
        ],
        'facebook_post' => [
            'short' => '20-40 words',
            'medium' => '40-100 words',
            'long' => '100-180 words',
        ],
        'blog_post' => [
            'short' => '400-600 words',
            'medium' => '800-1200 words',
            'long' => '1500-2000 words',
        ],
    ],
];
