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
use Illuminate\Support\Collection;
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
            ['name' => 'Arjun Mehta', 'email' => 'client@coachpay.test'],
            ['name' => 'Neha Gupta', 'email' => 'neha@example.com'],
            ['name' => 'Sam Fernandes', 'email' => 'sam@example.com'],
            ['name' => 'Ritu Kapoor', 'email' => null],
        ])->map(fn (array $data) => Client::factory()->for($trainer, 'trainer')->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'default_rate' => 1200,
        ]));

        // Give the first client a portal login.
        $clientUser = User::factory()->create([
            'name' => 'Arjun Mehta',
            'email' => 'client@coachpay.test',
            'password' => Hash::make('password'),
            'role' => UserRole::Client,
        ]);
        $clients->first()->update(['client_user_id' => $clientUser->id]);

        $reminders = app(InvoiceReminderService::class);
        $rate = 1200;

        foreach ($clients as $index => $client) {
            // Completed sessions: 4 go on the paid invoice, 2 on the open invoice,
            // 2 stay unbilled (to demo "create invoice from sessions").
            $completed = TrainingSession::factory()->count(8)->forClient($client)->create([
                'status' => SessionStatus::Completed,
                'scheduled_at' => fn () => CarbonImmutable::now()->subDays(random_int(1, 40)),
                'rate' => $rate,
            ])->values();

            // Upcoming + a couple of non-completed outcomes for status variety.
            TrainingSession::factory()->count(3)->forClient($client)->create([
                'status' => SessionStatus::Scheduled,
                'scheduled_at' => fn () => CarbonImmutable::now()->addDays(random_int(1, 12)),
                'rate' => $rate,
            ]);
            TrainingSession::factory()->forClient($client)->create([
                'status' => SessionStatus::Postponed,
                'scheduled_at' => CarbonImmutable::now()->addDays(4),
                'rate' => $rate,
                'notes' => 'Client travelling — rescheduling next week.',
            ]);
            TrainingSession::factory()->forClient($client)->create([
                'status' => SessionStatus::Cancelled,
                'scheduled_at' => CarbonImmutable::now()->subDays(6),
                'rate' => $rate,
            ]);

            // A paid invoice built from the first 4 completed sessions.
            $paid = Invoice::factory()->forClient($client)->create([
                'number' => 'PSS-'.str_pad((string) ($index * 3 + 1), 4, '0', STR_PAD_LEFT),
                'status' => InvoiceStatus::Paid,
                'issued_at' => CarbonImmutable::now()->subDays(30),
                'due_date' => CarbonImmutable::now()->subDays(23)->toDateString(),
                'paid_at' => CarbonImmutable::now()->subDays(25),
            ]);
            $this->attachSessions($paid, $completed->slice(0, 4), $rate);
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

            // An open invoice from the next 2 completed sessions (alternating overdue).
            $open = Invoice::factory()->forClient($client)->create([
                'number' => 'PSS-'.str_pad((string) ($index * 3 + 2), 4, '0', STR_PAD_LEFT),
                'status' => InvoiceStatus::Sent,
                'issued_at' => CarbonImmutable::now()->subDays(8),
                'due_date' => CarbonImmutable::now()->addDays($index % 2 === 0 ? 5 : -3)->toDateString(),
            ]);
            $this->attachSessions($open, $completed->slice(4, 2), $rate);
            $reminders->schedule($open->fresh());
        }

        RecurringInvoice::factory()->forClient($clients->first())->create([
            'interval' => 'month',
            'next_run_at' => CarbonImmutable::now()->addDays(3)->toDateString(),
            'auto_send' => true,
        ]);

        $this->command->info('Demo trainer: trainer@coachpay.test / password');
        $this->command->info('Demo client:  client@coachpay.test  / password');
    }

    /**
     * Attach completed sessions to an invoice as line items and recalc totals.
     *
     * @param  Collection<int, TrainingSession>  $sessions
     */
    private function attachSessions(Invoice $invoice, Collection $sessions, int $rate): void
    {
        foreach ($sessions as $session) {
            $invoice->items()->create([
                'description' => 'Training session — '.$session->scheduled_at->format('d M'),
                'quantity' => 1,
                'unit_amount' => $rate,
                'amount' => $rate,
                'training_session_id' => $session->id,
            ]);
            $session->update(['invoice_id' => $invoice->id]);
        }

        $invoice->load('items');
        $invoice->recalculateTotals();
        $invoice->save();
    }
}
