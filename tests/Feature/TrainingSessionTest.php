<?php

use App\Models\Client;
use App\Models\TrainingSession;
use App\Models\User;

test('a trainer can log a session for their client', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();

    $this->actingAs($trainer)->post(route('sessions.store'), [
        'client_id' => $client->id,
        'scheduled_at' => now()->addDay()->toDateTimeString(),
        'duration_minutes' => 60,
        'rate' => 1000,
        'status' => 'scheduled',
    ])->assertRedirect();

    $this->assertDatabaseHas('training_sessions', [
        'trainer_id' => $trainer->id,
        'client_id' => $client->id,
        'rate' => 1000,
    ]);
});

test('a trainer cannot log a session for someone elses client', function () {
    $trainer = User::factory()->trainer()->create();
    $foreignClient = Client::factory()->create();

    $this->actingAs($trainer)->post(route('sessions.store'), [
        'client_id' => $foreignClient->id,
        'scheduled_at' => now()->addDay()->toDateTimeString(),
        'duration_minutes' => 60,
        'rate' => 1000,
        'status' => 'scheduled',
    ])->assertStatus(422);

    $this->assertDatabaseCount('training_sessions', 0);
});

test('the sessions index only returns the current trainers sessions in range', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();

    TrainingSession::factory()->forClient($client)->create([
        'scheduled_at' => now(),
    ]);
    TrainingSession::factory()->create(['scheduled_at' => now()]);

    $this->actingAs($trainer)
        ->get(route('sessions.index'))
        ->assertInertia(fn ($page) => $page->component('sessions/index')->has('sessions', 1));
});

test('a trainer cannot delete another trainers session', function () {
    $trainer = User::factory()->trainer()->create();
    $session = TrainingSession::factory()->create();

    $this->actingAs($trainer)
        ->delete(route('sessions.destroy', $session))
        ->assertForbidden();
});
