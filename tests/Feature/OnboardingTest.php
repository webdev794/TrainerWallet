<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a fresh trainer sees the onboarding wizard', function () {
    $user = User::factory()->create();
    $user->trainerProfile()->create([
        'business_name' => 'Fit Co',
        'currency' => 'INR',
        'invoice_prefix' => 'FC',
    ]);

    $this->actingAs($user)->get(route('onboarding.show'))->assertOk();
});

test('app routes redirect to onboarding until it is completed', function () {
    $user = User::factory()->create();
    $user->trainerProfile()->create([
        'business_name' => 'Fit Co',
        'currency' => 'INR',
        'invoice_prefix' => 'FC',
    ]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('onboarding.show'));
});

test('completing onboarding stores the profile and unlocks the dashboard', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $user->trainerProfile()->create([
        'business_name' => 'Fit Co',
        'currency' => 'INR',
        'invoice_prefix' => 'FC',
    ]);

    $response = $this->actingAs($user)->post(route('onboarding.store'), [
        'business_name' => 'Iron Path Fitness',
        'currency' => 'USD',
        'upi_vpa' => 'ironpath@okhdfcbank',
        'invoice_prefix' => 'IPF',
        'logo' => UploadedFile::fake()->image('logo.png'),
    ]);

    $response->assertRedirect(route('dashboard'));

    $profile = $user->trainerProfile->fresh();

    expect($profile->business_name)->toBe('Iron Path Fitness')
        ->and($profile->currency)->toBe('USD')
        ->and($profile->invoice_prefix)->toBe('IPF')
        ->and($profile->onboarded_at)->not->toBeNull()
        ->and($profile->logo_path)->not->toBeNull();

    Storage::disk('public')->assertExists($profile->logo_path);

    $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk();
});

test('onboarding validates the upi id format', function () {
    $user = User::factory()->create();
    $user->trainerProfile()->create([
        'business_name' => 'Fit Co',
        'currency' => 'INR',
        'invoice_prefix' => 'FC',
    ]);

    $this->actingAs($user)->post(route('onboarding.store'), [
        'business_name' => 'Fit Co',
        'currency' => 'INR',
        'invoice_prefix' => 'FC',
        'upi_vpa' => 'not a vpa',
    ])->assertSessionHasErrors('upi_vpa');
});
