<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'public/Landing')->name('home');

Route::inertia('/dashboard', 'app/Dashboard')->name('dashboard');
Route::inertia('/generator', 'app/Generator')->name('generator');
Route::inertia('/tones', 'app/Tones')->name('tones');
Route::inertia('/history', 'app/History')->name('history');
Route::inertia('/billing', 'app/Billing')->name('billing');
Route::inertia('/settings', 'app/Settings')->name('settings');

Route::inertia('/admin', 'admin/Overview')->name('admin.overview');
Route::inertia('/admin/users', 'admin/Users')->name('admin.users');
Route::inertia('/admin/packages', 'admin/Packages')->name('admin.packages');
Route::inertia('/admin/tones', 'admin/BuiltInTones')->name('admin.tones');
Route::inertia('/admin/payments', 'admin/Payments')->name('admin.payments');
Route::inertia('/admin/monitoring', 'admin/Monitoring')->name('admin.monitoring');
Route::inertia('/admin/audit', 'admin/AuditLog')->name('admin.audit');
Route::inertia('/admin/ai', 'admin/AiSettings')->name('admin.ai');

Route::fallback(function (Request $request) {
    abort_unless($request->isMethod('GET') && ! $request->expectsJson(), 404);

    return Inertia::render('public/NotFound')->toResponse($request)->setStatusCode(404);
});
