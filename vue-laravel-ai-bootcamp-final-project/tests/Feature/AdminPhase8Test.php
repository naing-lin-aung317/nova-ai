<?php

use App\Enums\PaymentOrderStatus;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\CreditPackage;
use App\Models\PaymentOrder;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\NovaSettingsService;
use App\Services\WalletService;

test('admin can suspend user and adjust credits with audit trail', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->unverified()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.status', $target), ['status' => UserStatus::Suspended->value])
        ->assertRedirect();

    expect($target->fresh()->status)->toBe(UserStatus::Suspended);
    expect(AuditLog::query()->where('action', 'user.suspended')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin.users.credits', $target), [
            'delta' => 25,
            'reason' => 'Support goodwill credit',
        ])
        ->assertRedirect();

    $target->creditWallet?->refresh();
    expect($target->creditWallet?->available_balance)->toBe(25);
    expect(AuditLog::query()->where('action', 'wallet.adjusted')->exists())->toBeTrue();
});

test('admin can manually verify a pending user', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->unverified()->create();

    expect($target->status)->toBe(UserStatus::PendingVerification);
    expect($target->email_verified_at)->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.users.verify-email', $target))
        ->assertRedirect()
        ->assertSessionHas('status', 'admin.verifyEmailSuccess');

    $target->refresh();

    expect($target->email_verified_at)->not->toBeNull()
        ->and($target->status)->toBe(UserStatus::Active);

    $target->creditWallet?->refresh();

    expect($target->creditWallet?->available_balance)->toBe(WalletService::SIGNUP_BONUS_CREDITS);

    expect(AuditLog::query()->where('action', 'user.email_verified')->exists())->toBeTrue();
});

test('manual email verify is rejected when user is already verified', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.verify-email', $target))
        ->assertSessionHasErrors('user');
});

test('credit adjustment rejects amounts over cap', function () {
    $admin = User::factory()->superAdmin()->create();
    $target = User::factory()->unverified()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.credits', $target), [
            'delta' => 501,
            'reason' => 'Too many',
        ])
        ->assertSessionHasErrors('delta');
});

test('admin can create a credit package', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.packages.store'), [
            'name' => 'Studio',
            'credits' => 120,
            'price_usd' => 39.99,
        ])
        ->assertRedirect();

    $package = CreditPackage::query()->where('slug', 'studio')->first();

    expect($package)->not->toBeNull()
        ->and($package->credits)->toBe(120)
        ->and($package->price_cents)->toBe(3999)
        ->and($package->is_visible)->toBeTrue();
});

test('hiding package removes it from billing list', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();
    $package = CreditPackage::query()->where('slug', 'starter')->firstOrFail();

    $this->actingAs($admin)
        ->patch(route('admin.packages.update', $package), [
            'name' => $package->name,
            'credits' => $package->credits,
            'price_usd' => $package->priceUsd(),
            'is_visible' => false,
            'sort_order' => $package->sort_order,
        ])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('packages', 2));
});

test('manual verify only works for pending orders', function () {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->unverified()->create();
    $package = CreditPackage::query()->where('slug', 'starter')->firstOrFail();

    $order = PaymentOrder::query()->create([
        'user_id' => $user->id,
        'credit_package_id' => $package->id,
        'amount_cents' => $package->price_cents,
        'currency' => 'usd',
        'credits_to_issue' => $package->credits,
        'status' => PaymentOrderStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.payments.verify', $order))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(PaymentOrderStatus::Paid);
    expect(AuditLog::query()->where('action', 'payment.manual_verify')->exists())->toBeTrue();

    $user->creditWallet?->refresh();
    expect($user->creditWallet?->available_balance)->toBe(20);
});

test('ai settings persist and merge into config', function () {
    $admin = User::factory()->superAdmin()->create();

    $response = $this->actingAs($admin)
        ->patch(route('admin.ai.update'), [
            'gemini' => [
                'model' => 'gemini-test-model',
                'request_timeout_seconds' => 90,
                'reservation_timeout_minutes' => 4,
            ],
            'generation' => [
                'max_concurrent_per_user' => 2,
            ],
        ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(SystemSetting::query()->count())->toBe(4)
        ->and(AuditLog::query()->where('action', 'ai_settings.updated')->exists())->toBeTrue();

    expect(SystemSetting::query()->where('key', 'gemini.model')->value('value'))
        ->toBe('gemini-test-model');

    app(NovaSettingsService::class)->mergeIntoConfig();

    expect(config('nova.gemini.model'))->toBe('gemini-test-model')
        ->and(config('nova.generation.max_concurrent_per_user'))->toBe(2);
});

test('reconcile command reports healthy wallets', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.credits', $admin), [
            'delta' => 5,
            'reason' => 'Test reconciliation seed',
        ])
        ->assertRedirect();

    $this->artisan('nova:reconcile-wallets')->assertSuccessful();
});
