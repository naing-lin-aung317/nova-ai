<?php

namespace Database\Factories;

use App\Enums\ContentLanguage;
use App\Enums\ContentLength;
use App\Enums\ContentType;
use App\Enums\GenerationStatus;
use App\Models\BuiltInTone;
use App\Models\GenerationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GenerationRequest>
 */
class GenerationRequestFactory extends Factory
{
    protected $model = GenerationRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content_type' => ContentType::FacebookPost,
            'language' => ContentLanguage::English,
            'length' => ContentLength::Medium,
            'built_in_tone_id' => BuiltInTone::query()->value('id'),
            'custom_tone_id' => null,
            'topic' => fake()->sentence(),
            'audience' => null,
            'keywords' => null,
            'call_to_action' => null,
            'output_text' => fake()->paragraph(),
            'status' => GenerationStatus::Completed,
            'completed_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Completed,
            'output_text' => fake()->paragraph(),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => GenerationStatus::Failed,
            'output_text' => null,
            'failure_reason' => 'Generation failed. No credit was charged.',
            'completed_at' => now(),
        ]);
    }
}
