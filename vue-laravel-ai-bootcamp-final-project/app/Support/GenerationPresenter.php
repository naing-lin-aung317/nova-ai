<?php

namespace App\Support;

use App\Enums\GenerationStatus;
use App\Models\GenerationRequest;

class GenerationPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function listItem(GenerationRequest $generation): array
    {
        $generation->loadMissing(['builtInTone', 'customTone']);

        return [
            'id' => $generation->id,
            'content_type' => $generation->content_type->value,
            'language' => $generation->language->value,
            'length' => $generation->length->value,
            'status' => $generation->status->value,
            'topic' => $generation->topic,
            'tone_label' => self::toneLabel($generation) ?? '—',
            'credits_charged' => $generation->status === GenerationStatus::Completed ? 1 : 0,
            'can_delete' => self::canDelete($generation),
            'created_at' => $generation->created_at?->toIso8601String(),
            'detail_url' => route('history.show', $generation),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(GenerationRequest $generation): array
    {
        $generation->loadMissing(['builtInTone', 'customTone']);

        return [
            ...self::listItem($generation),
            'audience' => $generation->audience,
            'keywords' => $generation->keywords,
            'call_to_action' => $generation->call_to_action,
            'output_text' => $generation->output_text,
            'failure_reason' => $generation->failure_reason,
            'charged' => $generation->status === GenerationStatus::Completed,
            'no_charge' => in_array($generation->status, [
                GenerationStatus::Failed,
                GenerationStatus::Stopped,
            ], true),
            'completed_at' => $generation->completed_at?->toIso8601String(),
            'can_edit' => $generation->status === GenerationStatus::Completed,
            'can_delete' => self::canDelete($generation),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function apiPayload(GenerationRequest $generation): array
    {
        return [
            'id' => $generation->id,
            'status' => $generation->status->value,
            'content_type' => $generation->content_type->value,
            'language' => $generation->language->value,
            'length' => $generation->length->value,
            'topic' => $generation->topic,
            'output_text' => $generation->output_text,
            'failure_reason' => $generation->failure_reason,
            'charged' => $generation->status === GenerationStatus::Completed,
            'no_charge' => in_array($generation->status, [
                GenerationStatus::Failed,
                GenerationStatus::Stopped,
            ], true),
        ];
    }

    public static function canDelete(GenerationRequest $generation): bool
    {
        return in_array($generation->status, [
            GenerationStatus::Completed,
            GenerationStatus::Failed,
            GenerationStatus::Stopped,
        ], true);
    }

    protected static function toneLabel(GenerationRequest $generation): ?string
    {
        if ($generation->customTone !== null) {
            return $generation->customTone->name;
        }

        if ($generation->builtInTone !== null) {
            return $generation->builtInTone->name;
        }

        return null;
    }
}
