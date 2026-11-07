<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('renders public and auth inertia pages for guests', function (string $uri, string $component) {
    $this->get($uri)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'landing' => ['/', 'public/Landing'],
    'login' => ['/login', 'auth/Login'],
    'register' => ['/register', 'auth/Register'],
    'forgot password' => ['/forgot-password', 'auth/ForgotPassword'],
    'reset password' => ['/reset-password/example-token', 'auth/ResetPassword'],
]);

test('redirects guests away from app routes', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with([
    '/dashboard',
    '/generator',
    '/tones',
    '/history',
    '/billing',
    '/settings',
]);

test('authenticated users can open app screens', function (string $uri, string $component) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get($uri)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'dashboard' => ['/dashboard', 'app/Dashboard'],
    'generator' => ['/generator', 'app/Generator'],
    'tones' => ['/tones', 'app/Tones'],
    'history' => ['/history', 'app/History'],
    'billing' => ['/billing', 'app/Billing'],
    'settings' => ['/settings', 'app/Settings'],
]);

test('redirects guests away from admin routes', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with([
    '/admin',
    '/admin/users',
    '/admin/packages',
    '/admin/tones',
    '/admin/payments',
    '/admin/monitoring',
    '/admin/audit',
    '/admin/ai',
]);

test('super admins can open admin screens', function (string $uri, string $component) {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get($uri)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'admin overview' => ['/admin', 'admin/Overview'],
    'admin users' => ['/admin/users', 'admin/Users'],
    'admin packages' => ['/admin/packages', 'admin/Packages'],
    'admin tones' => ['/admin/tones', 'admin/BuiltInTones'],
    'admin payments' => ['/admin/payments', 'admin/Payments'],
    'admin monitoring' => ['/admin/monitoring', 'admin/Monitoring'],
    'admin audit' => ['/admin/audit', 'admin/AuditLog'],
    'admin ai' => ['/admin/ai', 'admin/AiSettings'],
]);

test('regular users cannot open admin screens', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

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
