<?php

namespace Database\Factories;

use App\Models\TrainerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TrainerProfile>
 */
class TrainerProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $businessName = fake()->firstName().' Fitness';

        return [
            'user_id' => User::factory(),
            'business_name' => $businessName,
            'currency' => 'INR',
            'upi_vpa' => Str::lower(fake()->userName()).'@okhdfcbank',
            'invoice_prefix' => TrainerProfile::defaultPrefixFor($businessName),
            'next_invoice_number' => 1,
            'plan' => 'free',
            'onboarded_at' => null,
        ];
    }

    public function onboarded(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarded_at' => now(),
        ]);
    }

    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => 'pro',
            'plan_renews_at' => now()->addMonth(),
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
            'onboarded_at' => now()->subMonths(2),
            'slug' => Str::slug((string) ($attributes['business_name'] ?? fake()->unique()->words(2, true))).'-'.fake()->unique()->numberBetween(100, 999),
            'headline' => 'Strength & conditioning for everyday athletes',
            'bio' => fake()->paragraph(),
            'city' => fake()->city(),
        ]);
    }
}
