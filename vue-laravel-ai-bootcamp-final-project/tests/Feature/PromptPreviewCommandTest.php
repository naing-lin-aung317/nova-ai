<?php

use App\Enums\ContentLength;
use App\Enums\ContentType;
use App\Support\ContentLengthGuide;
use Illuminate\Support\Facades\Artisan;

test('prompt preview prints assembled prompts without calling gemini', function () {
    expect(config('nova.content_length_ranges.facebook_post.medium'))->toBe('40-100 words');

    expect(ContentLengthGuide::promptLengthLine(
        ContentType::FacebookPost,
        ContentLength::Medium,
    ))->toBe('Length: Medium (40-100 words)');

    $exitCode = Artisan::call('prompt:preview', [
        'contentType' => 'facebook_post',
        'language' => 'en',
        'length' => 'medium',
    ]);

    expect($exitCode)->toBe(0);

    $output = Artisan::output();

    expect($output)
        ->toContain('=== SYSTEM INSTRUCTION ===')
        ->toContain('=== USER PROMPT ===')
        ->toContain('NOVA AI')
        ->toContain('--- USER INPUT (untrusted) ---')
        ->toContain('40-100 words')
        ->toContain('handmade soy candles');
});

test('prompt preview is blocked outside local and testing', function () {
    $previous = app()['env'];
    app()['env'] = 'production';

    try {
        $exitCode = Artisan::call('prompt:preview', [
            'contentType' => 'blog_post',
            'language' => 'en',
            'length' => 'long',
        ]);

        expect($exitCode)->toBe(1);
        expect(Artisan::output())->toContain('only available in local and testing');
    } finally {
        app()['env'] = $previous;
    }
});
