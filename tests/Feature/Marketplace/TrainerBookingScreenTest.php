<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;

test('the trainer bookings screen shows only their bookings', function () {
    $trainer = User::factory()->trainer()->create();
    Booking::factory()->create(['trainer_id' => $trainer->id]);
    Booking::factory()->create(); // another trainer

    $this->actingAs($trainer)
        ->get(route('bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('bookings/index')->has('bookings.data', 1));
});

test('a trainer can mark a booking completed which completes the session', function () {
    $profile = TrainerProfile::factory()->published()->create();
    $trainer = User::find($profile->user_id);
    $session = TrainingSession::factory()->create(['trainer_id' => $trainer->id]);
    $booking = Booking::factory()->create([
        'trainer_id' => $trainer->id,
        'training_session_id' => $session->id,
        'status' => BookingStatus::Confirmed,
    ]);

    $this->actingAs($trainer)
        ->put(route('bookings.update', $booking), ['status' => 'completed'])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Completed)
        ->and($session->fresh()->status->value)->toBe('completed');
});

test('a trainer cannot update another trainers booking', function () {
    $trainer = User::factory()->trainer()->create();
    $booking = Booking::factory()->create();

    $this->actingAs($trainer)
        ->put(route('bookings.update', $booking), ['status' => 'cancelled'])
        ->assertForbidden();
});
