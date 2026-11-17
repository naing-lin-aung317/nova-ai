<?php

use App\Http\Controllers\Admin\AiSettingsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BuiltInTonesController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PackagesController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\Settings\AccountController;
use App\Http\Controllers\Settings\PreferencesController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TonesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'public/Landing')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/generator', [GeneratorController::class, 'index'])->name('generator');
    Route::get('/tones', [TonesController::class, 'index'])->name('tones');
    Route::post('/tones', [TonesController::class, 'store'])->name('tones.store');
    Route::put('/tones/{customTone}', [TonesController::class, 'update'])->name('tones.update');
    Route::delete('/tones/{customTone}', [TonesController::class, 'destroy'])->name('tones.destroy');
    Route::get('/history', [HistoryController::class, 'index'])->name('history');
    Route::get('/history/{generation}', [HistoryController::class, 'show'])->name('history.show');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/checkout/{creditPackage}', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    Route::get('/billing/fake-checkout/{paymentOrder}', [BillingController::class, 'fakeCheckout'])
        ->name('billing.fake-checkout');
    Route::inertia('/settings', 'app/Settings')->name('settings');

    Route::patch('/settings/preferences', [PreferencesController::class, 'update'])
        ->name('settings.preferences.update');
    Route::delete('/settings/account', [AccountController::class, 'destroy'])
        ->name('settings.account.destroy');

    Route::post('/generations', [GenerationController::class, 'store'])->name('generations.store');
    Route::patch('/generations/{generation}', [GenerationController::class, 'update'])->name('generations.update');
    Route::delete('/generations/{generation}', [GenerationController::class, 'destroy'])->name('generations.destroy');
    Route::get('/generations/{generation}', [GenerationController::class, 'show'])->name('generations.show');
    Route::get('/generations/{generation}/stream', [GenerationController::class, 'stream'])->name('generations.stream');
    Route::post('/generations/{generation}/cancel', [GenerationController::class, 'cancel'])->name('generations.cancel');
    Route::post('/generations/{generation}/regenerate', [GenerationController::class, 'regenerate'])->name('generations.regenerate');
});

Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::middleware(['auth', 'super_admin'])->prefix('admin')->group(function () {
    Route::get('/', OverviewController::class)->name('admin.overview');
    Route::get('/users', [UsersController::class, 'index'])->name('admin.users');
    Route::patch('/users/{user}/status', [UsersController::class, 'updateStatus'])->name('admin.users.status');
    Route::post('/users/{user}/verify-email', [UsersController::class, 'verifyEmail'])->name('admin.users.verify-email');
    Route::post('/users/{user}/credits', [UsersController::class, 'adjustCredits'])->name('admin.users.credits');
    Route::get('/packages', [PackagesController::class, 'index'])->name('admin.packages');
    Route::post('/packages', [PackagesController::class, 'store'])->name('admin.packages.store');
    Route::patch('/packages/{creditPackage}', [PackagesController::class, 'update'])->name('admin.packages.update');
    Route::get('/tones', [BuiltInTonesController::class, 'index'])->name('admin.tones');
    Route::post('/tones', [BuiltInTonesController::class, 'store'])->name('admin.tones.store');
    Route::patch('/tones/{builtInTone}', [BuiltInTonesController::class, 'update'])->name('admin.tones.update');
    Route::delete('/tones/{builtInTone}', [BuiltInTonesController::class, 'destroy'])->name('admin.tones.destroy');
    Route::get('/payments', [PaymentsController::class, 'index'])->name('admin.payments');
    Route::post('/payments/{paymentOrder}/verify', [PaymentsController::class, 'verifyManual'])
        ->name('admin.payments.verify');
    Route::get('/monitoring', MonitoringController::class)->name('admin.monitoring');
    Route::get('/audit', AuditLogController::class)->name('admin.audit');
    Route::get('/ai', [AiSettingsController::class, 'index'])->name('admin.ai');
    Route::patch('/ai', [AiSettingsController::class, 'update'])->name('admin.ai.update');
});

Route::fallback(function (Request $request) {
    abort_unless($request->isMethod('GET') && ! $request->expectsJson(), 404);

    return Inertia::render('public/NotFound')->toResponse($request)->setStatusCode(404);
});
