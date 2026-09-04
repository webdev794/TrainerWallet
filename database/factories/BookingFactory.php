<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_user_id' => User::factory()->client(),
            'trainer_id' => User::factory()->trainer(),
            'package_id' => Package::factory(),
            'service_name' => 'Single session',
            'amount' => 1200,
            'currency' => 'INR',
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
            'status' => BookingStatus::Confirmed,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::Completed,
            'scheduled_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }
}
