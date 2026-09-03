<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomElement([1000, 2000, 3500, 5000]);

        return [
            'trainer_id' => User::factory(),
            'client_id' => Client::factory(),
            'number' => 'INV-'.fake()->unique()->numerify('####'),
            'status' => InvoiceStatus::Draft,
            'currency' => 'INR',
            'subtotal' => $total,
            'tax_total' => 0,
            'discount_total' => 0,
            'total' => $total,
            'amount_paid' => 0,
            'tax_rate' => 0,
            'issued_at' => null,
            'due_date' => now()->addDays(7)->toDateString(),
            'notes' => null,
            'public_token' => Str::random(40),
            'allowed_methods' => ['stripe', 'upi_manual'],
        ];
    }

    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'trainer_id' => $client->trainer_id,
            'client_id' => $client->id,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Sent,
            'issued_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::Paid,
            'issued_at' => now()->subDays(2),
            'amount_paid' => $attributes['total'],
            'paid_at' => now(),
        ]);
    }
}
