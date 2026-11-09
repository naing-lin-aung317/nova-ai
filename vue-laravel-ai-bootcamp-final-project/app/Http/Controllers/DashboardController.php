<?php

namespace App\Http\Controllers;

use App\Enums\ContentType;
use App\Enums\CreditTransactionType;
use App\Enums\GenerationStatus;
use App\Models\CreditTransaction;
use App\Models\GenerationRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(public WalletService $wallets) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $balances = $this->wallets->balancesForUser($user);
        $available = $balances['available'] ?? 0;

        $monthStart = Carbon::now()->startOfMonth();

        $generationsThisMonth = GenerationRequest::query()
            ->where('user_id', $user->id)
            ->where('status', GenerationStatus::Completed)
            ->where('completed_at', '>=', $monthStart)
            ->count();

        $creditsSpentThisMonth = (int) abs(CreditTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', CreditTransactionType::Capture)
            ->where('created_at', '>=', $monthStart)
            ->sum('amount'));

        $generationsTotal = GenerationRequest::query()
            ->where('user_id', $user->id)
            ->where('status', GenerationStatus::Completed)
            ->count();

        $recentContent = GenerationRequest::query()
            ->where('user_id', $user->id)
            ->where('status', GenerationStatus::Completed)
            ->latest('completed_at')
            ->limit(5)
            ->get()
            ->map(fn (GenerationRequest $generation): array => [
                'id' => $generation->id,
                'content_type' => $generation->content_type->value,
                'topic' => $generation->topic,
                'language' => $generation->language->value,
                'created_at' => $generation->completed_at?->toIso8601String()
                    ?? $generation->created_at?->toIso8601String(),
                'url' => route('history.show', $generation),
            ]);

        return Inertia::render('app/Dashboard', [
            'stats' => [
                'availableCredits' => $available,
                'generationsThisMonth' => $generationsThisMonth,
                'creditsSpentThisMonth' => $creditsSpentThisMonth,
                'generationsTotal' => $generationsTotal,
                'customTones' => $user->customTones()->count(),
            ],
            'contentTypes' => array_map(
                fn (ContentType $type): string => $type->value,
                ContentType::cases(),
            ),
            'recentContent' => $recentContent,
            'lowCredits' => $available > 0 && $available <= 3,
        ]);
    }
}
