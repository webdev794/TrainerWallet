<?php

namespace Database\Factories;

use App\Enums\PaymentGatewayType;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomElement([1000, 2000, 3500]);

        return [
            'invoice_id' => Invoice::factory(),
            'trainer_id' => User::factory(),
            'gateway' => PaymentGatewayType::Stripe,
            'gateway_order_id' => 'ord_'.Str::random(12),
            'gateway_payment_id' => null,
            'amount' => $amount,
            'currency' => 'INR',
            'fee_amount' => 0,
            'net_amount' => $amount,
            'status' => PaymentStatus::Pending,
            'idempotency_key' => Str::uuid()->toString(),
            'raw_payload' => null,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Succeeded,
            'gateway_payment_id' => 'pay_'.Str::random(12),
            'paid_at' => now(),
        ]);
    }
}
