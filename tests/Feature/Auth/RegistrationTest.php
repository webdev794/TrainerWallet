<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

test('the registration screen can be rendered', function () {
    $this->get(route('register'))->assertOk();
});

test('a new trainer can register and gets a profile', function () {
    Event::fake();

    $response = $this->post(route('register'), [
        'name' => 'Dana Coach',
        'email' => 'dana@example.com',
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('onboarding.show'));

    $user = User::firstWhere('email', 'dana@example.com');

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Trainer)
        ->and($user->trainerProfile)->not->toBeNull()
        ->and($user->trainerProfile->invoice_prefix)->toBe('DC')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Event::assertDispatched(Registered::class);
});

test('registration requires matching password confirmation', function () {
    $this->post(route('register'), [
        'name' => 'Dana Coach',
        'email' => 'dana@example.com',
        'password' => 'password1234',
        'password_confirmation' => 'nope',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});
