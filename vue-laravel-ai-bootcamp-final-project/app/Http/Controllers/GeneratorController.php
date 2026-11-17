<?php

namespace App\Http\Controllers;

use App\Enums\ContentLanguage;
use App\Enums\ContentLength;
use App\Enums\ContentType;
use App\Models\BuiltInTone;
use App\Models\GenerationRequest;
use App\Services\GenerationService;
use App\Support\ContentLengthGuide;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneratorController extends Controller
{
    public function index(Request $request, GenerationService $generations): Response
    {
        $generations->releaseStuckGenerations($request->user()->id);

        $activeGeneration = $generations->activeGenerationForUser($request->user());
        $activeGenerationPayload = $activeGeneration === null ? null : [
            'id' => $activeGeneration->id,
            'status' => $activeGeneration->status->value,
        ];

        $resume = null;

        if ($request->filled('resume')) {
            $generation = GenerationRequest::query()->find($request->integer('resume'));

            if ($generation !== null) {
                $this->authorize('view', $generation);

                $resume = [
                    'id' => $generation->id,
                    'status' => $generation->status->value,
                    'output_text' => $generation->output_text,
                ];
            }
        }

        $prefillTopic = $request->string('topic')->trim()->toString();

        return Inertia::render('app/Generator', [
            'prefill' => [
                'content_type' => $request->query('content_type'),
                'topic' => $prefillTopic !== '' ? $prefillTopic : null,
                'built_in_tone_id' => $request->filled('built_in_tone_id')
                    ? $request->integer('built_in_tone_id')
                    : null,
                'custom_tone_id' => $request->filled('custom_tone_id')
                    ? $request->integer('custom_tone_id')
                    : null,
            ],
            'builtInTones' => BuiltInTone::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'description']),
            'customTones' => $request->user()
                ->customTones()
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
            'contentTypes' => array_map(
                fn (ContentType $type): string => $type->value,
                ContentType::cases(),
            ),
            'contentLanguages' => array_map(
                fn (ContentLanguage $language): string => $language->value,
                ContentLanguage::cases(),
            ),
            'contentLengths' => array_map(
                fn (ContentLength $length): string => $length->value,
                ContentLength::cases(),
            ),
            'resume' => $resume,
            'activeGeneration' => $activeGenerationPayload,
            'briefMaxLength' => (int) config('nova.generation.topic_max_length', 1000),
            'lengthHintsByType' => ContentLengthGuide::hintsByTypeForUi(),
        ]);
    }
}
