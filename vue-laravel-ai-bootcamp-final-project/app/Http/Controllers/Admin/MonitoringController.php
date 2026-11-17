<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CreditReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\CreditReservation;
use App\Models\CreditWallet;
use App\Models\PaymentWebhookEvent;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function __invoke(Request $request, WalletService $wallets): Response
    {
        $mismatchIds = $wallets->walletIdsWithLedgerMismatch();

        $mismatches = CreditWallet::query()
            ->with('user:id,name,email')
            ->whereIn('id', $mismatchIds)
            ->limit(50)
            ->get()
            ->map(fn (CreditWallet $wallet): array => [
                'user_name' => $wallet->user?->name,
                'user_email' => $wallet->user?->email,
                'available' => $wallet->available_balance,
                'ledger_sum' => (int) $wallet->transactions()->sum('amount'),
            ]);

        $staleReservations = CreditReservation::query()
            ->where('status', CreditReservationStatus::Pending)
            ->where('expires_at', '<', now())
            ->count();

        $pendingReservations = CreditReservation::query()
            ->where('status', CreditReservationStatus::Pending)
            ->count();

        $webhooks = PaymentWebhookEvent::query()
            ->latest()
            ->limit(25)
            ->get(['id', 'stripe_event_id', 'event_type', 'payment_order_id', 'processed_at']);

        $mismatchCount = count($mismatchIds);

        return Inertia::render('admin/Monitoring', [
            'mismatchCount' => $mismatchCount,
            'mismatches' => $mismatches,
            'staleReservations' => $staleReservations,
            'pendingReservations' => $pendingReservations,
            'webhooks' => $webhooks,
            'allChecksClear' => $mismatchCount === 0 && $staleReservations === 0 && $pendingReservations === 0,
        ]);
    }
}
