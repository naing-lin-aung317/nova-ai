<?php

namespace App\Http\Requests;

use App\Models\CustomTone;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomToneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('customTone')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'sample_content' => ['nullable', 'string', 'max:'.CustomTone::SAMPLE_CONTENT_MAX_LENGTH],
        ];
    }
}
