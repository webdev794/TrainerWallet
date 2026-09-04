<?php

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PackageType;
use App\Mail\InvoiceSentMail;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\TrainerProfile;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');
    Storage::fake('public');
});

function publicTrainerWithSession(): array
{
    $profile = TrainerProfile::factory()->published()->create();
    $package = Package::factory()->bookable()->create([
        'trainer_id' => $profile->user_id,
        'type' => PackageType::Session,
        'amount' => 1500,
        'duration_minutes' => 45,
        'name' => 'Power hour',
    ]);

    return [User::find($profile->user_id), $package];
}

test('instant booking a session creates a client, a scheduled session and a sent invoice', function () {
    [$trainer, $package] = publicTrainerWithSession();
    $client = User::factory()->client()->create();

    $when = now()->addDays(2)->startOfHour();

    $this->actingAs($client)->post(route('portal.bookings.store'), [
        'package_id' => $package->id,
        'scheduled_at' => $when->toDateTimeString(),
    ])->assertRedirect();

    $booking = Booking::first();
    expect($booking->status)->toBe(BookingStatus::Confirmed)
        ->and($booking->client_user_id)->toBe($client->id)
        ->and((float) $booking->amount)->toBe(1500.0);

    $clientRecord = Client::where('trainer_id', $trainer->id)
        ->where('client_user_id', $client->id)->first();
    expect($clientRecord)->not->toBeNull();

    $session = TrainingSession::first();
    expect($session->client_id)->toBe($clientRecord->id)
        ->and($session->duration_minutes)->toBe(45)
        ->and($session->invoice_id)->toBe(Invoice::first()->id);

    $invoice = Invoice::first();
    expect($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and((float) $invoice->total)->toBe(1500.0);

    Mail::assertSent(InvoiceSentMail::class);
});

test('booking a session without a slot fails validation', function () {
    [, $package] = publicTrainerWithSession();
    $client = User::factory()->client()->create();

    $this->actingAs($client)->post(route('portal.bookings.store'), [
        'package_id' => $package->id,
    ])->assertSessionHasErrors('scheduled_at');
});

test('booking a monthly plan skips the session but still invoices', function () {
    $profile = TrainerProfile::factory()->published()->create();
    $package = Package::factory()->bookable()->create([
        'trainer_id' => $profile->user_id,
        'type' => PackageType::Monthly,
        'amount' => 8000,
    ]);
    $client = User::factory()->client()->create();

    $this->actingAs($client)->post(route('portal.bookings.store'), [
        'package_id' => $package->id,
    ])->assertRedirect();

    expect(TrainingSession::count())->toBe(0)
        ->and(Invoice::first()->status)->toBe(InvoiceStatus::Sent);
});

test('a client can book two different trainers and gets a client record for each', function () {
    [$trainerA, $packageA] = publicTrainerWithSession();
    [$trainerB, $packageB] = publicTrainerWithSession();
    $client = User::factory()->client()->create();

    foreach ([$packageA, $packageB] as $package) {
        $this->actingAs($client)->post(route('portal.bookings.store'), [
            'package_id' => $package->id,
            'scheduled_at' => now()->addDays(3)->toDateTimeString(),
        ])->assertRedirect();
    }

    expect($client->clientRecords()->count())->toBe(2)
        ->and(Client::where('trainer_id', $trainerA->id)->where('client_user_id', $client->id)->exists())->toBeTrue()
        ->and(Client::where('trainer_id', $trainerB->id)->where('client_user_id', $client->id)->exists())->toBeTrue();
});

test('a non-bookable package cannot be booked', function () {
    $profile = TrainerProfile::factory()->published()->create();
    $package = Package::factory()->create([
        'trainer_id' => $profile->user_id,
        'is_bookable' => false,
    ]);
    $client = User::factory()->client()->create();

    $this->actingAs($client)->post(route('portal.bookings.store'), [
        'package_id' => $package->id,
        'scheduled_at' => now()->addDay()->toDateTimeString(),
    ])->assertSessionHasErrors('package_id');
});

test('a client can cancel their own confirmed booking', function () {
    [, $package] = publicTrainerWithSession();
    $client = User::factory()->client()->create();

    $this->actingAs($client)->post(route('portal.bookings.store'), [
        'package_id' => $package->id,
        'scheduled_at' => now()->addDay()->toDateTimeString(),
    ]);

    $booking = Booking::first();

    $this->actingAs($client)
        ->put(route('portal.bookings.update', $booking), ['status' => 'cancelled'])
        ->assertRedirect();

    expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
        ->and(TrainingSession::first()->status->value)->toBe('cancelled');
});
