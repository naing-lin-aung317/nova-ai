<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PackagesController extends Controller
{
    public function __construct(public AuditLogService $audit) {}

    public function index(): Response
    {
        $packages = CreditPackage::query()
            ->withCount([
                'paymentOrders as sold_count' => fn ($query) => $query->where('status', PaymentOrderStatus::Paid),
            ])
            ->orderBy('sort_order')
            ->get()
            ->map(fn (CreditPackage $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'slug' => $package->slug,
                'credits' => $package->credits,
                'price_cents' => $package->price_cents,
                'price_usd' => $package->priceUsd(),
                'is_visible' => $package->is_visible,
                'sort_order' => $package->sort_order,
                'sold_count' => (int) $package->sold_count,
                'highlight' => $package->slug === 'agency',
            ]);

        $visible = $packages->where('is_visible', true);
        $cheapestPerCredit = $visible
            ->map(fn (array $package): float => (float) $package['price_usd'] / max(1, (int) $package['credits']))
            ->min();

        return Inertia::render('admin/Packages', [
            'packages' => $packages,
            'summary' => [
                'total' => $packages->count(),
                'visible' => $visible->count(),
                'cheapest_per_credit' => $cheapestPerCredit !== null
                    ? number_format($cheapestPerCredit, 2, '.', '')
                    : '0.00',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'credits' => ['required', 'integer', 'min:1', 'max:10000'],
            'price_usd' => ['required', 'numeric', 'min:1', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:255'],
            'is_visible' => ['sometimes', 'boolean'],
        ]);

        $priceCents = (int) round((float) $data['price_usd'] * 100);

        if ($priceCents < 100) {
            throw ValidationException::withMessages([
                'price_usd' => __('validation.min.numeric', ['attribute' => 'price', 'min' => 1]),
            ]);
        }

        $slug = $this->uniqueSlug(Str::slug($data['name']));

        $sortOrder = $data['sort_order']
            ?? ((int) CreditPackage::query()->max('sort_order')) + 1;

        $creditPackage = CreditPackage::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'credits' => $data['credits'],
            'price_cents' => $priceCents,
            'currency' => 'usd',
            'is_visible' => $request->boolean('is_visible', true),
            'sort_order' => $sortOrder,
        ]);

        $this->audit->record(
            actor: $request->user(),
            action: 'package.created',
            subject: $creditPackage,
            reason: null,
            metadata: ['package' => $creditPackage->only(['name', 'slug', 'credits', 'price_cents'])],
            request: $request,
        );

        return back();
    }

    public function update(Request $request, CreditPackage $creditPackage): RedirectResponse
    {
        $request->merge([
            'is_visible' => $request->boolean('is_visible'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'credits' => ['required', 'integer', 'min:1', 'max:10000'],
            'price_usd' => ['required', 'numeric', 'min:1', 'max:10000'],
            'is_visible' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:255'],
        ]);

        $priceCents = (int) round((float) $data['price_usd'] * 100);

        if ($priceCents < 100) {
            throw ValidationException::withMessages([
                'price_usd' => __('validation.min.numeric', ['attribute' => 'price', 'min' => 1]),
            ]);
        }

        $payload = [
            'name' => $data['name'],
            'credits' => $data['credits'],
            'price_cents' => $priceCents,
            'is_visible' => $data['is_visible'],
            'sort_order' => $data['sort_order'],
        ];

        $before = $creditPackage->only(array_keys($payload));

        $creditPackage->forceFill($payload)->save();

        $this->audit->record(
            actor: $request->user(),
            action: 'package.updated',
            subject: $creditPackage,
            reason: null,
            metadata: ['before' => $before, 'after' => $payload],
            request: $request,
        );

        return back();
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = $base !== '' ? $base : 'package';
        $candidate = $slug;
        $suffix = 0;

        while (CreditPackage::query()->where('slug', $candidate)->exists()) {
            $suffix++;
            $candidate = $slug.'-'.$suffix;
        }

        return $candidate;
    }
}
