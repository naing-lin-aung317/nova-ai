<?php

use App\Models\User;

test('super admin gate blocks non admins', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
});

test('super admin gate allows super admins', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertOk();
});
