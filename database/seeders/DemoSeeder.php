<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentGatewayType;
use App\Enums\PaymentStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\RecurringInvoice;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\InvoiceReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $trainer = User::factory()->create([
            'name' => 'Priya Sharma',
            'email' => 'trainer@coachpay.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Trainer,
        ]);

        $trainer->trainerProfile()->create([
            'business_name' => 'Priya Sharma Strength',
            'currency' => 'INR',
            'upi_vpa' => 'priya@okhdfcbank',
            'invoice_prefix' => 'PSS',
            'next_invoice_number' => 1,
            'plan' => 'pro',
            'plan_renews_at' => CarbonImmutable::now()->addMonth(),
            'onboarded_at' => CarbonImmutable::now()->subMonths(3),
        ]);

        $packages = collect([
            ['name' => 'Single session', 'type' => 'session', 'amount' => 1200],
            ['name' => '10-session pack', 'type' => 'package', 'amount' => 10000, 'sessions_count' => 10],
            ['name' => 'Monthly unlimited', 'type' => 'monthly', 'amount' => 8000, 'billing_interval' => 'month'],
        ])->map(fn (array $data) => Package::create([...$data, 'trainer_id' => $trainer->id]));

        $clients = collect([
            ['name' => 'Arjun Mehta', 'email' => 'arjun@example.com'],
            ['name' => 'Neha Gupta', 'email' => 'neha@example.com'],
            ['name' => 'Sam Fernandes', 'email' => 'sam@example.com'],
            ['name' => 'Ritu Kapoor', 'email' => null],
        ])->map(fn (array $data) => Client::factory()->for($trainer, 'trainer')->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'default_rate' => 1200,
        ]));

        $reminders = app(InvoiceReminderService::class);

        foreach ($clients as $index => $client) {
            TrainingSession::factory()->count(6)->forClient($client)->create([
                'status' => SessionStatus::Completed,
                'scheduled_at' => fn () => CarbonImmutable::now()->subDays(random_int(1, 40)),
                'rate' => 1200,
            ]);
            TrainingSession::factory()->count(2)->forClient($client)->create([
                'status' => SessionStatus::Scheduled,
                'scheduled_at' => fn () => CarbonImmutable::now()->addDays(random_int(1, 10)),
            ]);

            // A paid invoice.
            $paid = Invoice::factory()->forClient($client)->create([
                'number' => 'PSS-'.str_pad((string) ($index * 3 + 1), 4, '0', STR_PAD_LEFT),
                'status' => InvoiceStatus::Paid,
                'issued_at' => CarbonImmutable::now()->subDays(30),
                'due_date' => CarbonImmutable::now()->subDays(23)->toDateString(),
                'paid_at' => CarbonImmutable::now()->subDays(25),
            ]);
            $paid->items()->create(['description' => '4 sessions', 'quantity' => 4, 'unit_amount' => 1200, 'amount' => 4800]);
            $paid->load('items');
            $paid->recalculateTotals();
            $paid->amount_paid = $paid->total;
            $paid->save();
            $paid->payments()->create([
                'trainer_id' => $trainer->id,
                'gateway' => PaymentGatewayType::Stripe,
                'amount' => $paid->total,
                'currency' => 'INR',
                'fee_amount' => round((float) $paid->total * 0.02, 2),
                'net_amount' => round((float) $paid->total * 0.98, 2),
                'status' => PaymentStatus::Succeeded,
                'paid_at' => CarbonImmutable::now()->subDays(25),
                'idempotency_key' => Str::uuid()->toString(),
            ]);

            // An open invoice (some overdue).
            $open = Invoice::factory()->forClient($client)->create([
                'number' => 'PSS-'.str_pad((string) ($index * 3 + 2), 4, '0', STR_PAD_LEFT),
                'status' => InvoiceStatus::Sent,
                'issued_at' => CarbonImmutable::now()->subDays(8),
                'due_date' => CarbonImmutable::now()->addDays($index % 2 === 0 ? 5 : -3)->toDateString(),
            ]);
            $open->items()->create(['description' => 'Monthly unlimited', 'quantity' => 1, 'unit_amount' => 8000, 'amount' => 8000]);
            $open->load('items');
            $open->recalculateTotals();
            $open->save();
            $reminders->schedule($open->fresh());
        }

        RecurringInvoice::factory()->forClient($clients->first())->create([
            'interval' => 'month',
            'next_run_at' => CarbonImmutable::now()->addDays(3)->toDateString(),
            'auto_send' => true,
        ]);

        $this->command->info('Demo trainer: trainer@coachpay.test / password');
    }
}
