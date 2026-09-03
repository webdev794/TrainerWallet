<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomElement([1, 2, 4]);
        $unit = fake()->randomElement([800, 1000, 1500]);

        return [
            'invoice_id' => Invoice::factory(),
            'description' => fake()->randomElement(['Personal training session', 'Monthly plan', 'Assessment']),
            'quantity' => $quantity,
            'unit_amount' => $unit,
            'amount' => $quantity * $unit,
            'training_session_id' => null,
        ];
    }
}
