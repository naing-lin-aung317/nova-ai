<?php

namespace App\Http\Controllers;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
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

        $recentTransactions = CreditTransaction::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (CreditTransaction $transaction): array => [
                'id' => $transaction->id,
                'type' => $transaction->type->value,
                'label' => $this->transactionLabel($transaction),
                'amount' => $transaction->amount,
                'created_at' => $transaction->created_at?->toIso8601String(),
            ]);

        return Inertia::render('app/Dashboard', [
            'stats' => [
                'availableCredits' => $available,
                'generations' => 0,
                'customTones' => $user->customTones()->count(),
            ],
            'recentTransactions' => $recentTransactions,
            'recentContent' => [],
            'lowCredits' => $available > 0 && $available <= 3,
        ]);
    }

    protected function transactionLabel(CreditTransaction $transaction): string
    {
        if ($transaction->description !== null && $transaction->description !== '') {
            return $transaction->description;
        }

        return match ($transaction->type) {
            CreditTransactionType::SignupBonus => 'Signup bonus',
            CreditTransactionType::Purchase => 'Purchase',
            CreditTransactionType::Capture => 'Generation',
            CreditTransactionType::Release => 'Released · no charge',
            CreditTransactionType::Refund => 'Refund',
            CreditTransactionType::AdminAdjustment => 'Adjustment',
            default => $transaction->type->value,
        };
    }
}
