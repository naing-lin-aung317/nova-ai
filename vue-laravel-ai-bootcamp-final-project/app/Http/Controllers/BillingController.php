<?php

namespace App\Http\Controllers;

use App\Models\CreditPackage;
use App\Models\PaymentOrder;
use App\Services\PaymentService;
use App\Services\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function __construct(
        public PaymentService $payments,
        public WalletService $wallets,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $packages = CreditPackage::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CreditPackage $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'credits' => $package->credits,
                'price_cents' => $package->price_cents,
                'price_usd' => $package->priceUsd(),
                'highlight' => $package->slug === 'agency',
            ]);

        $orders = PaymentOrder::query()
            ->with('creditPackage:id,name,slug')
            ->where('user_id', $user->id)
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (PaymentOrder $order): array => [
                'id' => $order->id,
                'package_name' => $order->creditPackage?->name,
                'amount_usd' => $order->amountUsd(),
                'credits' => $order->credits_to_issue,
                'status' => $order->status->value,
                'created_at' => $order->created_at?->toIso8601String(),
            ]);

        return Inertia::render('app/Billing', [
            'packages' => $packages,
            'orders' => $orders,
            'stripeFake' => (bool) config('nova.stripe.fake'),
        ]);
    }

    public function checkout(Request $request, CreditPackage $creditPackage): RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $url = $this->payments->beginCheckout($request->user(), $creditPackage);

        return Inertia::location($url);
    }

    public function fakeCheckout(Request $request, PaymentOrder $paymentOrder): RedirectResponse
    {
        abort_unless(config('nova.stripe.fake'), 404);
        abort_unless((int) $paymentOrder->user_id === (int) $request->user()->id, 403);

        $this->payments->fulfillFakeCheckout($paymentOrder);

        return redirect()
            ->route('billing.success')
            ->with('status', 'billing.checkoutSuccessPending');
    }

    public function success(): Response
    {
        return Inertia::render('app/BillingSuccess');
    }

    public function cancel(): RedirectResponse
    {
        return redirect()
            ->route('billing.index')
            ->with('status', 'billing.checkoutCancelled');
    }
}
