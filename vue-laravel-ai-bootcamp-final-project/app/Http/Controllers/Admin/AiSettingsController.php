<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\NovaSettingsService;
use App\Support\AiFailureDiagnostics;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AiSettingsController extends Controller
{
    public function __construct(
        public NovaSettingsService $settings,
        public AuditLogService $audit,
    ) {}

    public function index(): Response
    {
        return Inertia::render('admin/AiSettings', [
            'settings' => $this->settings->valuesForAdmin(),
            'flags' => [
                'gemini_key_configured' => filled(config('ai.gemini.api_key')),
                'gemini_model_configured' => filled(config('ai.gemini.model')),
                'stripe_fake' => (bool) config('nova.stripe.fake'),
            ],
            'diagnostics' => AiFailureDiagnostics::forAdmin(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $nested = $request->only(['gemini', 'generation']);
        if ($nested === []) {
            $nested = [
                'gemini' => [
                    'model' => data_get($request->all(), 'gemini.model'),
                    'request_timeout_seconds' => data_get($request->all(), 'gemini.request_timeout_seconds'),
                    'reservation_timeout_minutes' => data_get($request->all(), 'gemini.reservation_timeout_minutes'),
                ],
                'generation' => [
                    'max_concurrent_per_user' => data_get($request->all(), 'generation.max_concurrent_per_user'),
                ],
            ];
        }

        $data = validator($nested, [
            'gemini.model' => ['required', 'string', 'max:120'],
            'gemini.request_timeout_seconds' => ['required', 'integer', 'min:30', 'max:600'],
            'gemini.reservation_timeout_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'generation.max_concurrent_per_user' => ['required', 'integer', 'min:1', 'max:5'],
        ])->validate();

        foreach (array_keys(NovaSettingsService::EDITABLE_KEYS) as $key) {
            $value = data_get($data, $key);

            if ($value === null) {
                continue;
            }

            $this->settings->update($key, $value);
        }

        $this->audit->record(
            actor: $request->user(),
            action: 'ai_settings.updated',
            reason: null,
            metadata: ['settings' => $data],
            request: $request,
        );

        return back();
    }
}
