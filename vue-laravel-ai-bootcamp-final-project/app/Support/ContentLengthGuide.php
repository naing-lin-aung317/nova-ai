<?php

namespace App\Support;

use App\Enums\ContentLength;
use App\Enums\ContentType;

class ContentLengthGuide
{
    /**
     * @return array<string, string>
     */
    public static function rangesForType(ContentType $type): array
    {
        $configured = config('nova.content_length_ranges.'.$type->value);

        if (is_array($configured) && $configured !== []) {
            return $configured;
        }

        /** @var array<string, string> $default */
        $default = config('nova.content_length_ranges.default', []);

        return $default;
    }

    public static function promptLengthLine(ContentType $type, ContentLength $length): string
    {
        $ranges = self::rangesForType($type);
        $rangeLabel = $ranges[$length->value] ?? $length->value;
        $title = ucfirst($length->value);

        return "Length: {$title} ({$rangeLabel})";
    }

    public static function uiHint(ContentType $type, ContentLength $length): string
    {
        $ranges = self::rangesForType($type);
        $rangeLabel = $ranges[$length->value] ?? '';

        return $rangeLabel !== '' ? ucfirst($rangeLabel) : ucfirst($length->value);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function hintsByTypeForUi(): array
    {
        /** @var array<string, string> $default */
        $default = config('nova.content_length_ranges.default', []);

        $matrix = [
            'default' => $default,
        ];

        foreach (ContentType::cases() as $type) {
            $matrix[$type->value] = self::rangesForType($type);
        }

        return array_map(
            fn (array $ranges): array => array_map(
                fn (string $label): string => ucfirst($label),
                $ranges,
            ),
            $matrix,
        );
    }
}
