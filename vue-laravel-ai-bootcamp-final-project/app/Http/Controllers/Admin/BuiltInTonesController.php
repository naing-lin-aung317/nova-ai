<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuiltInTone;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BuiltInTonesController extends Controller
{
    public function __construct(public AuditLogService $audit) {}

    public function index(): Response
    {
        $tones = BuiltInTone::query()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'description', 'is_active', 'sort_order']);

        return Inertia::render('admin/BuiltInTones', [
            'tones' => $tones,
            'summary' => [
                'total' => $tones->count(),
                'active' => $tones->where('is_active', true)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'is_active' => $request->boolean('is_active'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('built_in_tones', 'name')],
            'description' => ['required', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:255'],
        ]);

        $tone = BuiltInTone::query()->create($data);

        $this->audit->record(
            actor: $request->user(),
            action: 'tone.created',
            subject: $tone,
            request: $request,
        );

        return back();
    }

    public function update(Request $request, BuiltInTone $builtInTone): RedirectResponse
    {
        $request->merge([
            'is_active' => $request->boolean('is_active'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('built_in_tones', 'name')->ignore($builtInTone->id)],
            'description' => ['required', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:255'],
        ]);

        $before = $builtInTone->only(array_keys($data));
        $builtInTone->forceFill($data)->save();

        $this->audit->record(
            actor: $request->user(),
            action: 'tone.updated',
            subject: $builtInTone,
            metadata: ['before' => $before, 'after' => $data],
            request: $request,
        );

        return back();
    }

    public function destroy(Request $request, BuiltInTone $builtInTone): RedirectResponse
    {
        $snapshot = $builtInTone->only(['name', 'description', 'is_active', 'sort_order']);

        $toneId = $builtInTone->id;
        $builtInTone->delete();

        $this->audit->record(
            actor: $request->user(),
            action: 'tone.deleted',
            subject: null,
            metadata: ['deleted' => $snapshot, 'tone_id' => $toneId],
            request: $request,
        );

        return back();
    }
}
