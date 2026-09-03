<?php

use App\Models\Package;
use App\Models\User;

test('a trainer can create and list packages', function () {
    $trainer = User::factory()->trainer()->create();

    $this->actingAs($trainer)->post(route('packages.store'), [
        'name' => '10 session pack',
        'type' => 'package',
        'amount' => 9000,
        'sessions_count' => 10,
        'is_active' => true,
    ])->assertRedirect();

    $this->assertDatabaseHas('packages', [
        'trainer_id' => $trainer->id,
        'name' => '10 session pack',
        'sessions_count' => 10,
    ]);

    $this->actingAs($trainer)
        ->get(route('packages.index'))
        ->assertInertia(fn ($page) => $page->component('packages/index')->has('packages', 1));
});

test('a trainer cannot edit another trainers package', function () {
    $trainer = User::factory()->trainer()->create();
    $package = Package::factory()->create();

    $this->actingAs($trainer)
        ->put(route('packages.update', $package), [
            'name' => 'x',
            'type' => 'session',
            'amount' => 1,
        ])
        ->assertForbidden();
});

test('a trainer can delete their package', function () {
    $trainer = User::factory()->trainer()->create();
    $package = Package::factory()->for($trainer, 'trainer')->create();

    $this->actingAs($trainer)
        ->delete(route('packages.destroy', $package))
        ->assertRedirect();

    $this->assertDatabaseMissing('packages', ['id' => $package->id]);
});
