<?php

namespace App\Http\Requests;

use App\Enums\ContentLanguage;
use App\Enums\ContentLength;
use App\Enums\ContentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content_type' => ['required', Rule::enum(ContentType::class)],
            'language' => ['required', Rule::enum(ContentLanguage::class)],
            'length' => ['required', Rule::enum(ContentLength::class)],
            'built_in_tone_id' => ['nullable', 'integer', 'exists:built_in_tones,id'],
            'custom_tone_id' => ['nullable', 'integer', Rule::exists('custom_tones', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id))],
            'topic' => ['required', 'string', 'max:'.config('nova.generation.topic_max_length', 1000)],
            'audience' => ['nullable', 'string', 'max:255'],
            'keywords' => ['nullable', 'string', 'max:255'],
            'call_to_action' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('built_in_tone_id') && $this->filled('custom_tone_id')) {
                $validator->errors()->add('tone', 'Choose either a built-in tone or a custom tone, not both.');
            }

        });
    }

    public function idempotencyKey(): ?string
    {
        $header = $this->header('Idempotency-Key');

        return is_string($header) && $header !== '' ? $header : null;
    }
}
