<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\GeneratorController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\Settings\AccountController;
use App\Http\Controllers\Settings\PreferencesController;
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
    Route::inertia('/billing', 'app/Billing')->name('billing');
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
