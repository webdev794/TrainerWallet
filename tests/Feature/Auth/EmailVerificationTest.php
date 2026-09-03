<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('unverified trainers are redirected to the verification notice', function () {
    $user = User::factory()->unverified()->trainer()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('the email can be verified via a signed url', function () {
    Event::fake();

    $user = User::factory()->unverified()->trainer()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->get($url)->assertRedirect();

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('a verified trainer reaches the dashboard', function () {
    $user = User::factory()->trainer()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});
