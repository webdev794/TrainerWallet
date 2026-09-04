<?php

use App\Models\Package;
use App\Models\Review;
use App\Models\TrainerProfile;
use App\Models\User;

test('the directory lists only public trainers', function () {
    $public = TrainerProfile::factory()->published()->create(['business_name' => 'Open Gym']);
    Package::factory()->bookable()->create(['trainer_id' => $public->user_id]);

    TrainerProfile::factory()->create(['business_name' => 'Hidden Gym']); // not public

    $this->get(route('trainers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('trainers/index')
            ->has('trainers.data', 1)
            ->where('trainers.data.0.business_name', 'Open Gym'));
});

test('a public trainer profile shows bookable services and reviews', function () {
    $profile = TrainerProfile::factory()->published()->create();
    $bookable = Package::factory()->bookable()->create([
        'trainer_id' => $profile->user_id,
        'name' => 'Intro session',
    ]);
    Package::factory()->create(['trainer_id' => $profile->user_id, 'is_bookable' => false]);

    Review::factory()->create([
        'package_id' => $bookable->id,
        'trainer_id' => $profile->user_id,
        'rating' => 5,
        'body' => 'Brilliant',
    ]);

    $this->get(route('trainers.show', $profile->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('trainers/show')
            ->where('trainer.business_name', $profile->business_name)
            ->has('packages', 1)
            ->where('packages.0.name', 'Intro session')
            ->has('reviews', 1));
});

test('a non-public trainer profile 404s', function () {
    $profile = TrainerProfile::factory()->create(['slug' => 'ghost-gym', 'is_public' => false]);

    $this->get(route('trainers.show', 'ghost-gym'))->assertNotFound();
});

test('enabling the public profile generates a slug', function () {
    $trainer = User::factory()->trainer()->create();
    $trainer->trainerProfile()->update(['business_name' => 'Peak Form Studio', 'slug' => null]);

    $this->actingAs($trainer)->put(route('settings.update'), [
        'business_name' => 'Peak Form Studio',
        'currency' => 'INR',
        'invoice_prefix' => 'PFS',
        'is_public' => true,
        'headline' => 'Move better',
    ])->assertRedirect();

    expect($trainer->trainerProfile->fresh()->slug)->toBe('peak-form-studio')
        ->and($trainer->trainerProfile->fresh()->is_public)->toBeTrue();
});
