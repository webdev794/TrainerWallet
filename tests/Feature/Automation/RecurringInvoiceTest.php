<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');
});

test('the recurring index page renders', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    RecurringInvoice::factory()->forClient($client)->create();

    $this->actingAs($trainer)
        ->get(route('recurring.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('recurring/index')->has('schedules', 1));
});

test('a trainer can create a recurring schedule', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();

    $this->actingAs($trainer)->post(route('recurring.store'), [
        'client_id' => $client->id,
        'interval' => 'month',
        'due_days' => 7,
        'next_run_at' => now()->toDateString(),
        'auto_send' => true,
        'items' => [['description' => 'Monthly plan', 'quantity' => 1, 'unit_amount' => 8000]],
    ])->assertRedirect();

    $this->assertDatabaseHas('recurring_invoices', [
        'trainer_id' => $trainer->id,
        'client_id' => $client->id,
        'interval' => 'month',
    ]);
});

test('the generate command creates an invoice and advances the schedule', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create(['email' => 'c@example.com']);

    $schedule = RecurringInvoice::factory()->forClient($client)->create([
        'next_run_at' => CarbonImmutable::now()->toDateString(),
        'interval' => 'month',
        'auto_send' => true,
    ]);

    $this->artisan('invoices:generate-recurring')->assertSuccessful();

    $invoice = Invoice::where('recurring_invoice_id', $schedule->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and((float) $invoice->total)->toBe(8000.0);

    expect($schedule->fresh()->next_run_at->toDateString())
        ->toBe(CarbonImmutable::now()->addMonth()->toDateString());
});

test('a schedule that is not yet due is left alone', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();

    RecurringInvoice::factory()->forClient($client)->create([
        'next_run_at' => CarbonImmutable::now()->addWeek()->toDateString(),
    ]);

    $this->artisan('invoices:generate-recurring')->assertSuccessful();

    expect(Invoice::count())->toBe(0);
});

test('a trainer can trigger a schedule immediately', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    $schedule = RecurringInvoice::factory()->forClient($client)->create(['auto_send' => false]);

    $this->actingAs($trainer)
        ->post(route('recurring.run', $schedule))
        ->assertRedirect();

    expect(Invoice::where('recurring_invoice_id', $schedule->id)->count())->toBe(1);
});

test('a trainer cannot touch another trainers schedule', function () {
    $trainer = User::factory()->trainer()->create();
    $schedule = RecurringInvoice::factory()->create();

    $this->actingAs($trainer)
        ->delete(route('recurring.destroy', $schedule))
        ->assertForbidden();
});
