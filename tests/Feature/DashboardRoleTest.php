<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('it_support user sees admin dashboard component', function () {
    $user = User::factory()->create(['role' => 'it_support']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Admin')
        );
});

test('manager user sees manager dashboard component', function () {
    $user = User::factory()->create(['role' => 'manager']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Manager')
        );
});

test('supervisor user sees supervisor dashboard component', function () {
    $user = User::factory()->create(['role' => 'supervisor']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Supervisor')
        );
});

test('kasir user sees kasir dashboard component', function () {
    $user = User::factory()->create(['role' => 'kasir']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/Kasir')
        );
});
