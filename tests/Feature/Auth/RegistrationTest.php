<?php

use App\Enums\UserRole;
use App\Models\Client;
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
        'role' => 'trainer',
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

test('a new client can register and lands on the portal', function () {
    Event::fake();

    $response = $this->post(route('register'), [
        'name' => 'Casey Client',
        'email' => 'casey@example.com',
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
        'role' => 'client',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('portal.index'));

    $user = User::firstWhere('email', 'casey@example.com');
    expect($user->role)->toBe(UserRole::Client)
        ->and($user->trainerProfile)->toBeNull();
});

test('a client registering with a pre-existing invite email is linked to that record', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create([
        'email' => 'linked@example.com',
        'client_user_id' => null,
    ]);

    $this->post(route('register'), [
        'name' => 'Linked Client',
        'email' => 'linked@example.com',
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
        'role' => 'client',
    ]);

    $user = User::firstWhere('email', 'linked@example.com');
    expect($client->fresh()->client_user_id)->toBe($user->id);
});

test('adding a client whose email already self-registered links them automatically', function () {
    $trainer = User::factory()->trainer()->create();
    $clientUser = User::factory()->client()->create(['email' => 'selfmade@example.com']);

    $this->actingAs($trainer)->post(route('clients.store'), [
        'name' => 'Self Made',
        'email' => 'selfmade@example.com',
        'status' => 'active',
    ]);

    $client = Client::firstWhere('email', 'selfmade@example.com');
    expect($client->client_user_id)->toBe($clientUser->id);
});

test('the role must be trainer or client', function () {
    $this->post(route('register'), [
        'name' => 'X',
        'email' => 'x@example.com',
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
        'role' => 'admin',
    ])->assertSessionHasErrors('role');
});

test('registration requires matching password confirmation', function () {
    $this->post(route('register'), [
        'name' => 'Dana Coach',
        'email' => 'dana@example.com',
        'password' => 'password1234',
        'password_confirmation' => 'nope',
        'role' => 'trainer',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
});
