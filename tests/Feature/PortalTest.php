<?php

use App\Models\Client;
use App\Models\TrainingSession;
use App\Models\User;

test('a client sees only their own sessions in the portal', function () {
    $clientUser = User::factory()->client()->create();
    $client = Client::factory()->invited()->create(['client_user_id' => $clientUser->id]);

    TrainingSession::factory()->forClient($client)->count(2)->create();
    TrainingSession::factory()->create();

    $this->actingAs($clientUser)
        ->get(route('portal.sessions'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('portal/sessions')->has('sessions', 2));
});

test('a trainer cannot access the client portal', function () {
    $trainer = User::factory()->trainer()->create();

    $this->actingAs($trainer)->get(route('portal.index'))->assertForbidden();
});

test('a client is redirected to the portal after login', function () {
    $clientUser = User::factory()->client()->create();

    $this->post(route('login'), [
        'email' => $clientUser->email,
        'password' => 'password',
    ])->assertRedirect(route('portal.index'));
});
