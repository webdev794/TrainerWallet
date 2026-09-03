<?php

use App\Enums\UserRole;
use App\Models\Client;
use App\Models\User;
use App\Notifications\ClientInvitationNotification;
use Illuminate\Support\Facades\Notification;

test('a trainer can invite a client to the portal', function () {
    Notification::fake();

    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create([
        'email' => 'portal.client@example.com',
        'client_user_id' => null,
    ]);

    $this->actingAs($trainer)
        ->post(route('clients.invite', $client))
        ->assertRedirect();

    $client->refresh();

    expect($client->client_user_id)->not->toBeNull();

    $user = User::find($client->client_user_id);
    expect($user->role)->toBe(UserRole::Client)
        ->and($user->hasVerifiedEmail())->toBeTrue();

    Notification::assertSentTo($user, ClientInvitationNotification::class);
});

test('inviting a client without an email fails', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create(['email' => null]);

    $this->actingAs($trainer)
        ->post(route('clients.invite', $client))
        ->assertSessionHasErrors('email');
});

test('inviting an already invited client is a no-op', function () {
    Notification::fake();

    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->invited()->create();

    $this->actingAs($trainer)
        ->post(route('clients.invite', $client))
        ->assertRedirect();

    Notification::assertNothingSent();
});

test('a trainer cannot invite another trainers client', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->create(['email' => 'x@example.com']);

    $this->actingAs($trainer)
        ->post(route('clients.invite', $client))
        ->assertForbidden();
});
