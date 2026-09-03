<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\RecurringInvoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringInvoice>
 */
class RecurringInvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trainer_id' => User::factory(),
            'client_id' => Client::factory(),
            'template' => [
                'items' => [
                    ['description' => 'Monthly coaching', 'quantity' => 1, 'unit_amount' => 8000],
                ],
                'discount_total' => 0,
                'tax_rate' => 0,
                'notes' => null,
                'allowed_methods' => ['upi_manual', 'stripe'],
            ],
            'interval' => 'month',
            'due_days' => 7,
            'auto_send' => true,
            'next_run_at' => now()->toDateString(),
            'status' => 'active',
        ];
    }

    public function forClient(Client $client): static
    {
        return $this->state(fn (array $attributes) => [
            'trainer_id' => $client->trainer_id,
            'client_id' => $client->id,
        ]);
    }
}
