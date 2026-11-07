<?php

use App\Http\Controllers\Settings\AccountController;
use App\Http\Controllers\Settings\PreferencesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'public/Landing')->name('home');

Route::middleware('auth')->group(function () {
    Route::inertia('/dashboard', 'app/Dashboard')->name('dashboard');
    Route::inertia('/generator', 'app/Generator')->name('generator');
    Route::inertia('/tones', 'app/Tones')->name('tones');
    Route::inertia('/history', 'app/History')->name('history');
    Route::inertia('/billing', 'app/Billing')->name('billing');
    Route::inertia('/settings', 'app/Settings')->name('settings');

    Route::patch('/settings/preferences', [PreferencesController::class, 'update'])
        ->name('settings.preferences.update');
    Route::delete('/settings/account', [AccountController::class, 'destroy'])
        ->name('settings.account.destroy');
});

Route::middleware(['auth', 'super_admin'])->prefix('admin')->group(function () {
    Route::inertia('/', 'admin/Overview')->name('admin.overview');
    Route::inertia('/users', 'admin/Users')->name('admin.users');
    Route::inertia('/packages', 'admin/Packages')->name('admin.packages');
    Route::inertia('/tones', 'admin/BuiltInTones')->name('admin.tones');
    Route::inertia('/payments', 'admin/Payments')->name('admin.payments');
    Route::inertia('/monitoring', 'admin/Monitoring')->name('admin.monitoring');
    Route::inertia('/audit', 'admin/AuditLog')->name('admin.audit');
    Route::inertia('/ai', 'admin/AiSettings')->name('admin.ai');
});

Route::fallback(function (Request $request) {
    abort_unless($request->isMethod('GET') && ! $request->expectsJson(), 404);

    return Inertia::render('public/NotFound')->toResponse($request)->setStatusCode(404);
});
