<?php

namespace App\Console\Commands;

use App\Enums\ContentLanguage;
use App\Enums\ContentLength;
use App\Enums\ContentType;
use App\Models\BuiltInTone;
use App\Models\GenerationRequest;
use App\Services\GenerationPromptBuilder;
use Illuminate\Console\Command;
use ValueError;

class PromptPreviewCommand extends Command
{
    protected $signature = 'prompt:preview
                            {contentType : Content type enum value, e.g. facebook_post}
                            {language : Content language code: en or my}
                            {length : Length: short, medium, or long}';

    protected $description = 'Print system and user prompts as sent to Gemini (local dev only; no API call)';

    public function handle(GenerationPromptBuilder $builder): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('The prompt:preview command is only available in local and testing environments.');

            return self::FAILURE;
        }

        try {
            $contentType = ContentType::from($this->argument('contentType'));
            $language = $this->resolveLanguage((string) $this->argument('language'));
            $length = ContentLength::from(strtolower((string) $this->argument('length')));
        } catch (ValueError) {
            $this->error('Invalid content type, language, or length. Use enum values such as facebook_post, en, medium.');

            return self::FAILURE;
        }

        $generation = new GenerationRequest([
            'content_type' => $contentType,
            'language' => $language,
            'length' => $length,
            'built_in_tone_id' => BuiltInTone::query()->value('id'),
            'custom_tone_id' => null,
            'topic' => 'Sample brief: launch a seasonal promo for handmade soy candles with free local delivery.',
            'audience' => null,
            'keywords' => null,
            'call_to_action' => null,
        ]);

        $system = $builder->systemInstruction($generation);
        $user = $builder->userPrompt($generation);

        $this->line('=== SYSTEM INSTRUCTION ===');
        $this->line($system);
        $this->newLine();
        $this->line('=== USER PROMPT ===');
        $this->line($user);

        return self::SUCCESS;
    }

    protected function resolveLanguage(string $value): ContentLanguage
    {
        $normalized = strtolower(trim($value));

        return match ($normalized) {
            'en', 'english' => ContentLanguage::English,
            'my', 'burmese', 'mm' => ContentLanguage::Burmese,
            default => ContentLanguage::from($normalized),
        };
    }
}
