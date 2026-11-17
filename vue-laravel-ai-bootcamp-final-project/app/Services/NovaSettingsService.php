<?php

namespace App\Services;

use App\Models\SystemSetting;

class NovaSettingsService
{
    /** @var array<string, string> */
    public const EDITABLE_KEYS = [
        'gemini.model' => 'nova.gemini.model',
        'gemini.request_timeout_seconds' => 'nova.gemini.request_timeout_seconds',
        'gemini.reservation_timeout_minutes' => 'nova.gemini.reservation_timeout_minutes',
        'generation.max_concurrent_per_user' => 'nova.generation.max_concurrent_per_user',
    ];

    public function mergeIntoConfig(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        foreach (self::EDITABLE_KEYS as $dbKey => $configPath) {
            $stored = SystemSetting::query()->where('key', $dbKey)->value('value');

            if ($stored === null || $stored === '') {
                continue;
            }

            config([$configPath => $this->castValue($configPath, $stored)]);
        }
    }

    /**
     * @return array<string, int|string>
     */
    public function valuesForAdmin(): array
    {
        $out = [];

        foreach (self::EDITABLE_KEYS as $dbKey => $configPath) {
            $out[$dbKey] = config($configPath);
        }

        return $out;
    }

    public function update(string $key, int|string $value): void
    {
        abort_unless(array_key_exists($key, self::EDITABLE_KEYS), 404);

        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value],
        );

        config([self::EDITABLE_KEYS[$key] => $this->castValue(self::EDITABLE_KEYS[$key], (string) $value)]);
    }

    protected function tableExists(): bool
    {
        try {
            SystemSetting::query()->limit(1)->get();
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    protected function castValue(string $configPath, string $value): int|string
    {
        if (str_contains($configPath, 'model')) {
            return $value;
        }

        return (int) $value;
    }
}
