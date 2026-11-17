<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use App\Services\AuditLogService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentsController extends Controller
{
    public function __construct(
        public PaymentService $payments,
        public AuditLogService $audit,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status')->trim()->toString();

        $orders = PaymentOrder::query()
            ->with(['user:id,name,email', 'creditPackage:id,name'])
            ->when(
                $status !== '' && $status !== 'all',
                fn ($query) => $query->where('status', PaymentOrderStatus::from($status)),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PaymentOrder $order): array => [
                'id' => $order->id,
                'user_name' => $order->user?->name,
                'user_email' => $order->user?->email,
                'package_name' => $order->creditPackage?->name,
                'amount_usd' => $order->amountUsd(),
                'credits' => $order->credits_to_issue,
                'status' => $order->status->value,
                'stripe_checkout_session_id' => $order->stripe_checkout_session_id,
                'stripe_payment_intent_id' => $order->stripe_payment_intent_id,
                'fulfilled_by_admin_at' => $order->fulfilled_by_admin_at?->toIso8601String(),
                'created_at' => $order->created_at?->toIso8601String(),
            ]);

        $paidSummary = PaymentOrder::query()
            ->where('status', PaymentOrderStatus::Paid)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount_cents), 0) as cents')
            ->first();

        return Inertia::render('admin/Payments', [
            'orders' => $orders,
            'filters' => ['status' => $status === '' ? 'all' : $status],
            'summary' => [
                'paid_count' => (int) ($paidSummary->count ?? 0),
                'paid_total_usd' => number_format(((int) ($paidSummary->cents ?? 0)) / 100, 2, '.', ''),
            ],
        ]);
    }

    public function verifyManual(Request $request, PaymentOrder $paymentOrder): RedirectResponse
    {
        try {
            $fulfilled = $this->payments->fulfillManualByAdmin($paymentOrder, $request->user());
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        }

        if ($fulfilled) {
            $this->audit->record(
                actor: $request->user(),
                action: 'payment.manual_verify',
                subject: $paymentOrder->fresh(),
                reason: 'Manual payment verification',
                request: $request,
            );
        }

        return back();
    }
}
