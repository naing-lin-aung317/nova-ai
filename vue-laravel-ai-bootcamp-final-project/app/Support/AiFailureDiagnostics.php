<?php

namespace App\Support;

use App\Models\ApiUsageLog;
use Illuminate\Support\Carbon;

class AiFailureDiagnostics
{
    /**
     * @return array{
     *     since: string,
     *     totals: array<string, int>,
     *     last_failure: array{error_code: string, occurred_at: string, duration_ms: int}|null
     * }
     */
    public static function forAdmin(int $hours = 24): array
    {
        $since = Carbon::now()->subHours($hours);

        /** @var array<string, int> $totals */
        $totals = ApiUsageLog::query()
            ->where('success', false)
            ->where('created_at', '>=', $since)
            ->whereNotNull('error_code')
            ->selectRaw('error_code, count(*) as total')
            ->groupBy('error_code')
            ->pluck('total', 'error_code')
            ->map(fn ($count) => (int) $count)
            ->all();

        $last = ApiUsageLog::query()
            ->where('success', false)
            ->whereNotNull('error_code')
            ->orderByDesc('id')
            ->first(['error_code', 'duration_ms', 'created_at']);

        return [
            'since' => $since->toIso8601String(),
            'totals' => $totals,
            'last_failure' => $last === null ? null : [
                'error_code' => (string) $last->error_code,
                'occurred_at' => $last->created_at?->toIso8601String() ?? '',
                'duration_ms' => (int) $last->duration_ms,
            ],
        ];
    }
}
