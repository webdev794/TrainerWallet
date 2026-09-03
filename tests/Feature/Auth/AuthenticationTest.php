<?php

use App\Models\User;

test('the login screen can be rendered', function () {
    $this->get(route('login'))->assertOk();
});

test('a trainer can authenticate and land on the dashboard', function () {
    $user = User::factory()->trainer()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('an un-onboarded trainer is pushed to onboarding', function () {
    $user = User::factory()->create();
    $user->trainerProfile()->create([
        'business_name' => 'X',
        'currency' => 'INR',
        'invoice_prefix' => 'X',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.show'));
});

test('users cannot authenticate with an invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login is rate limited after five failed attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $ignored) {
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Too many login attempts');
});

test('an authenticated user can log out', function () {
    $user = User::factory()->trainer()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));

    $this->assertGuest();
});
