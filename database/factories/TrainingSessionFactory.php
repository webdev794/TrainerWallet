<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\Client;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trainer_id' => User::factory(),
            'client_id' => Client::factory(),
            'scheduled_at' => fake()->dateTimeBetween('-2 weeks', '+2 weeks'),
            'duration_minutes' => fake()->randomElement([45, 60, 90]),
            'rate' => fake()->randomElement([800, 1000, 1500]),
            'status' => SessionStatus::Scheduled,
            'notes' => null,
            'invoice_id' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::Completed,
            'scheduled_at' => fake()->dateTimeBetween('-2 weeks', '-1 day'),
        ]);
    }

    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'trainer_id' => $client->trainer_id,
            'client_id' => $client->id,
        ]);
    }
}
