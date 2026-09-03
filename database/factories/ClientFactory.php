<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trainer_id' => User::factory(),
            'client_user_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+91##########'),
            'default_rate' => fake()->randomElement([800, 1000, 1500, 2000]),
            'payment_preference' => fake()->randomElement(['upi', 'card', null]),
            'notes' => null,
            'status' => ClientStatus::Active,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ClientStatus::Archived]);
    }

    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_user_id' => User::factory()->client(),
        ]);
    }
}
