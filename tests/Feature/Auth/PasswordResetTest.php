<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('the forgot password screen can be rendered', function () {
    $this->get(route('password.request'))->assertOk();
});

test('a reset link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('a password can be reset with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password-1234',
            'password_confirmation' => 'new-password-1234',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('login'));

        return true;
    });
});
