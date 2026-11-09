<?php

namespace App\Http\Controllers;

use App\Enums\ContentLanguage;
use App\Enums\ContentType;
use App\Enums\GenerationStatus;
use App\Models\GenerationRequest;
use App\Support\GenerationPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HistoryController extends Controller
{
    /**
     * @var list<string>
     */
    protected const STATUS_TABS = [
        'completed',
        'failed',
        'stopped',
        'streaming',
        'all',
    ];

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'content_type' => ['nullable', Rule::enum(ContentType::class)],
            'language' => ['nullable', Rule::enum(ContentLanguage::class)],
            'status' => ['nullable', 'string', Rule::in(self::STATUS_TABS)],
        ]);

        $statusFilter = $validated['status'] ?? null;

        if ($statusFilter === null) {
            $statusFilter = $request->filled('q') ? 'all' : 'completed';
        }

        $userId = $request->user()->id;

        $statusCounts = $this->statusCountsForUser($userId);

        $query = GenerationRequest::query()
            ->where('user_id', $userId)
            ->with(['builtInTone:id,name', 'customTone:id,name'])
            ->latest('created_at');

        if (! empty($validated['q'])) {
            $needle = '%'.$validated['q'].'%';
            $query->where(function ($builder) use ($needle): void {
                $builder
                    ->where('topic', 'like', $needle)
                    ->orWhere('keywords', 'like', $needle)
                    ->orWhere('output_text', 'like', $needle);
            });
        }

        if (! empty($validated['content_type'])) {
            $query->where('content_type', $validated['content_type']);
        }

        if (! empty($validated['language'])) {
            $query->where('language', $validated['language']);
        }

        $this->applyStatusFilter($query, $statusFilter);

        $generations = $query
            ->paginate(15)
            ->withQueryString()
            ->through(fn (GenerationRequest $generation): array => GenerationPresenter::listItem($generation));

        return Inertia::render('app/History', [
            'generations' => $generations,
            'statusCounts' => $statusCounts,
            'filters' => [
                'q' => $validated['q'] ?? '',
                'content_type' => $validated['content_type'] ?? '',
                'language' => $validated['language'] ?? '',
                'status' => $statusFilter,
            ],
        ]);
    }

    public function show(GenerationRequest $generation): Response
    {
        $this->authorize('view', $generation);

        return Inertia::render('app/HistoryShow', [
            'generation' => GenerationPresenter::detail($generation),
        ]);
    }

    /**
     * @return array<string, int>
     */
    protected function statusCountsForUser(int $userId): array
    {
        $base = GenerationRequest::query()->where('user_id', $userId);

        return [
            'completed' => (clone $base)->where('status', GenerationStatus::Completed)->count(),
            'failed' => (clone $base)->where('status', GenerationStatus::Failed)->count(),
            'stopped' => (clone $base)->where('status', GenerationStatus::Stopped)->count(),
            'streaming' => (clone $base)->whereIn('status', [
                GenerationStatus::Pending,
                GenerationStatus::Streaming,
            ])->count(),
            'all' => (clone $base)->count(),
        ];
    }

    /**
     * @param  Builder<GenerationRequest>  $query
     */
    protected function applyStatusFilter($query, string $statusFilter): void
    {
        if ($statusFilter === 'all') {
            return;
        }

        if ($statusFilter === 'streaming') {
            $query->whereIn('status', [
                GenerationStatus::Pending,
                GenerationStatus::Streaming,
            ]);

            return;
        }

        $query->where('status', $statusFilter);
    }
}
