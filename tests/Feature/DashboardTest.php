<?php

use App\Models\User;

test('guests are redirected from the dashboard to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('an onboarded trainer can view the dashboard', function () {
    $user = User::factory()->trainer()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard'));
});

test('a client landing on the app is routed to the portal', function () {
    $user = User::factory()->client()->create();

    $this->actingAs($user)->get(route('portal.index'))->assertOk();
});
