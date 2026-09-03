<?php

use App\Models\Client;
use App\Models\User;

test('a trainer sees only their own clients', function () {
    $trainer = User::factory()->trainer()->create();
    $other = User::factory()->trainer()->create();

    Client::factory()->for($trainer, 'trainer')->create(['name' => 'Mine']);
    Client::factory()->for($other, 'trainer')->create(['name' => 'Theirs']);

    $this->actingAs($trainer)
        ->get(route('clients.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('clients/index')
            ->has('clients.data', 1)
            ->where('clients.data.0.name', 'Mine'));
});

test('a trainer can create a client', function () {
    $trainer = User::factory()->trainer()->create();

    $this->actingAs($trainer)->post(route('clients.store'), [
        'name' => 'Alex Lifter',
        'email' => 'alex@example.com',
        'phone' => '+919999999999',
        'default_rate' => 1200,
        'payment_preference' => 'upi',
        'status' => 'active',
    ])->assertRedirect();

    $this->assertDatabaseHas('clients', [
        'trainer_id' => $trainer->id,
        'name' => 'Alex Lifter',
        'default_rate' => 1200,
    ]);
});

test('a trainer cannot update another trainers client', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->create();

    $this->actingAs($trainer)
        ->put(route('clients.update', $client), [
            'name' => 'Hacked',
            'status' => 'active',
        ])
        ->assertForbidden();
});

test('a trainer can delete their client', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();

    $this->actingAs($trainer)
        ->delete(route('clients.destroy', $client))
        ->assertRedirect();

    $this->assertDatabaseMissing('clients', ['id' => $client->id]);
});

test('clients can be filtered by search term', function () {
    $trainer = User::factory()->trainer()->create();
    Client::factory()->for($trainer, 'trainer')->create(['name' => 'Priya Sharma']);
    Client::factory()->for($trainer, 'trainer')->create(['name' => 'Rahul Verma']);

    $this->actingAs($trainer)
        ->get(route('clients.index', ['search' => 'priya']))
        ->assertInertia(fn ($page) => $page->has('clients.data', 1)
            ->where('clients.data.0.name', 'Priya Sharma'));
});

test('a client role is redirected away from the clients screen', function () {
    $client = User::factory()->client()->create();

    $this->actingAs($client)
        ->get(route('clients.index'))
        ->assertRedirect(route('portal.index'));
});
