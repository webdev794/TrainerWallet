<?php

use App\Models\Booking;
use App\Models\Package;
use App\Models\Review;
use App\Models\TrainerProfile;
use App\Models\User;

function bookedService(User $client): Package
{
    $profile = TrainerProfile::factory()->published()->create();
    $package = Package::factory()->bookable()->create(['trainer_id' => $profile->user_id]);

    Booking::factory()->create([
        'client_user_id' => $client->id,
        'trainer_id' => $profile->user_id,
        'package_id' => $package->id,
    ]);

    return $package;
}

test('a client can review a service they booked and it updates the trainer rating', function () {
    $client = User::factory()->client()->create();
    $package = bookedService($client);

    $this->actingAs($client)->post(route('portal.reviews.store'), [
        'package_id' => $package->id,
        'rating' => 4,
        'body' => 'Solid coaching',
        'improvement' => 'A bit more warm-up time',
    ])->assertRedirect();

    $this->assertDatabaseHas('reviews', [
        'package_id' => $package->id,
        'client_user_id' => $client->id,
        'rating' => 4,
    ]);

    expect((float) TrainerProfile::where('user_id', $package->trainer_id)->value('rating_avg'))->toBe(4.0)
        ->and((int) TrainerProfile::where('user_id', $package->trainer_id)->value('rating_count'))->toBe(1);
});

test('a client cannot review a service they never booked', function () {
    $client = User::factory()->client()->create();
    $profile = TrainerProfile::factory()->published()->create();
    $package = Package::factory()->bookable()->create(['trainer_id' => $profile->user_id]);

    $this->actingAs($client)->post(route('portal.reviews.store'), [
        'package_id' => $package->id,
        'rating' => 5,
    ])->assertForbidden();
});

test('reviewing the same service again updates the existing review', function () {
    $client = User::factory()->client()->create();
    $package = bookedService($client);

    $this->actingAs($client)->post(route('portal.reviews.store'), ['package_id' => $package->id, 'rating' => 3]);
    $this->actingAs($client)->post(route('portal.reviews.store'), ['package_id' => $package->id, 'rating' => 5]);

    expect(Review::where('package_id', $package->id)->where('client_user_id', $client->id)->count())->toBe(1)
        ->and(Review::where('package_id', $package->id)->value('rating'))->toBe(5);
});

test('a client can delete their review and the rating recomputes', function () {
    $client = User::factory()->client()->create();
    $package = bookedService($client);

    $this->actingAs($client)->post(route('portal.reviews.store'), ['package_id' => $package->id, 'rating' => 5]);
    $review = Review::first();

    $this->actingAs($client)->delete(route('portal.reviews.destroy', $review))->assertRedirect();

    expect(Review::count())->toBe(0)
        ->and((int) TrainerProfile::where('user_id', $package->trainer_id)->value('rating_count'))->toBe(0);
});

test('a client cannot edit someone elses review', function () {
    $owner = User::factory()->client()->create();
    $package = bookedService($owner);
    $this->actingAs($owner)->post(route('portal.reviews.store'), ['package_id' => $package->id, 'rating' => 4]);
    $review = Review::first();

    $intruder = User::factory()->client()->create();

    $this->actingAs($intruder)
        ->put(route('portal.reviews.update', $review), ['rating' => 1])
        ->assertForbidden();
});

test('the trainer reviews screen aggregates by service', function () {
    $client = User::factory()->client()->create();
    $package = bookedService($client);
    $this->actingAs($client)->post(route('portal.reviews.store'), ['package_id' => $package->id, 'rating' => 4]);

    $trainer = User::find($package->trainer_id);

    $this->actingAs($trainer)
        ->get(route('reviews.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('reviews/index')
            ->has('reviews', 1)
            ->has('byService', 1)
            ->where('summary.rating_count', 1));
});
