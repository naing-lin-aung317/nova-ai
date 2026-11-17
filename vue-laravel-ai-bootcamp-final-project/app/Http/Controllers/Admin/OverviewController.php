<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GenerationStatus;
use App\Enums\PaymentOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\GenerationRequest;
use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $generations24h = GenerationRequest::query()
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $failed24h = GenerationRequest::query()
            ->where('created_at', '>=', now()->subDay())
            ->where('status', GenerationStatus::Failed)
            ->count();

        $revenueCents30d = (int) PaymentOrder::query()
            ->where('status', PaymentOrderStatus::Paid)
            ->where('paid_at', '>=', now()->subDays(30))
            ->sum('amount_cents');

        return Inertia::render('admin/Overview', [
            'stats' => [
                'users' => User::query()->count(),
                'verified_users' => User::query()->whereNotNull('email_verified_at')->count(),
                'generations_24h' => $generations24h,
                'failed_generations_24h' => $failed24h,
                'payments_paid_30d' => PaymentOrder::query()
                    ->where('status', PaymentOrderStatus::Paid)
                    ->where('paid_at', '>=', now()->subDays(30))
                    ->count(),
                'revenue_usd_30d' => number_format($revenueCents30d / 100, 2, '.', ''),
            ],
            'recentUsers' => User::query()
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at?->toIso8601String(),
                ]),
            'recentPaidOrders' => PaymentOrder::query()
                ->with(['user:id,name', 'creditPackage:id,name'])
                ->where('status', PaymentOrderStatus::Paid)
                ->latest('paid_at')
                ->limit(5)
                ->get()
                ->map(fn (PaymentOrder $order): array => [
                    'id' => $order->id,
                    'user_name' => $order->user?->name,
                    'package_name' => $order->creditPackage?->name,
                    'amount_usd' => $order->amountUsd(),
                    'paid_at' => $order->paid_at?->toIso8601String(),
                ]),
        ]);
    }
}
