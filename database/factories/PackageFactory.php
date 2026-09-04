<?php

namespace Database\Factories;

use App\Enums\PackageType;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(PackageType::cases());

        return [
            'trainer_id' => User::factory(),
            'name' => Str::title(fake()->word().' '.fake()->word()),
            'type' => $type,
            'amount' => fake()->randomElement([1000, 5000, 8000, 12000]),
            'sessions_count' => $type === PackageType::Package ? fake()->randomElement([5, 10, 20]) : null,
            'billing_interval' => $type === PackageType::Monthly ? 'month' : null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function bookable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_bookable' => true,
            'is_active' => true,
            'description' => 'A focused one-on-one session tailored to your goals.',
            'duration_minutes' => 60,
        ]);
    }
}
