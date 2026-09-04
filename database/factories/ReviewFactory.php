<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'trainer_id' => User::factory()->trainer(),
            'client_user_id' => User::factory()->client(),
            'rating' => fake()->numberBetween(3, 5),
            'body' => fake()->sentence(),
            'improvement' => fake()->optional()->sentence(),
            'is_published' => true,
        ];
    }
}
