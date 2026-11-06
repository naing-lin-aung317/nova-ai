<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('renders an inertia page for each screen', function (string $uri, string $component) {
    $this->get($uri)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'landing' => ['/', 'public/Landing'],
    'login' => ['/login', 'auth/Login'],
    'register' => ['/register', 'auth/Register'],
    'forgot password' => ['/forgot-password', 'auth/ForgotPassword'],
    'reset password' => ['/reset-password/example-token', 'auth/ResetPassword'],
    'dashboard' => ['/dashboard', 'app/Dashboard'],
    'generator' => ['/generator', 'app/Generator'],
    'tones' => ['/tones', 'app/Tones'],
    'history' => ['/history', 'app/History'],
    'billing' => ['/billing', 'app/Billing'],
    'settings' => ['/settings', 'app/Settings'],
    'admin overview' => ['/admin', 'admin/Overview'],
    'admin users' => ['/admin/users', 'admin/Users'],
    'admin packages' => ['/admin/packages', 'admin/Packages'],
    'admin tones' => ['/admin/tones', 'admin/BuiltInTones'],
    'admin payments' => ['/admin/payments', 'admin/Payments'],
    'admin monitoring' => ['/admin/monitoring', 'admin/Monitoring'],
    'admin audit' => ['/admin/audit', 'admin/AuditLog'],
    'admin ai' => ['/admin/ai', 'admin/AiSettings'],
]);

test('renders the email verification page for a signed-in user', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/VerifyEmail'));
});

test('returns the not found page for an unknown address', function () {
    $this->get('/not-a-real-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('public/NotFound'));
});

test('returns 404 for an unknown json request', function () {
    $this->getJson('/api/v1/missing')->assertNotFound();
});
