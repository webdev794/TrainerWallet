<?php

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('sending an invoice materialises its reminder schedule', function () {
    Mail::fake();
    Storage::fake('local');

    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create(['email' => 'c@example.com']);
    $invoice = Invoice::factory()->forClient($client)->create([
        'due_date' => CarbonImmutable::now()->addDays(10)->toDateString(),
    ]);
    $invoice->items()->create(['description' => 'x', 'quantity' => 1, 'unit_amount' => 1000, 'amount' => 1000]);

    $this->actingAs($trainer)->post(route('invoices.send', $invoice))->assertRedirect();

    // pre_due [-3, -1] + overdue [1, 3, 7] = 5 rows, all future => pending
    expect(Reminder::where('invoice_id', $invoice->id)->count())->toBe(5)
        ->and(Reminder::where('invoice_id', $invoice->id)->where('status', 'pending')->count())->toBe(5);
});

test('reminders whose date has already passed are recorded as skipped', function () {
    Mail::fake();
    Storage::fake('local');

    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create(['email' => 'c@example.com']);
    $invoice = Invoice::factory()->forClient($client)->create([
        'due_date' => CarbonImmutable::now()->subDays(1)->toDateString(),
    ]);
    $invoice->items()->create(['description' => 'x', 'quantity' => 1, 'unit_amount' => 1000, 'amount' => 1000]);

    $this->actingAs($trainer)->post(route('invoices.send', $invoice));

    expect(Reminder::where('invoice_id', $invoice->id)->where('status', 'skipped')->count())->toBeGreaterThan(0);
});

test('the dispatch command emails due reminders and skips paid invoices', function () {
    Mail::fake();

    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create(['email' => 'due@example.com']);

    $open = Invoice::factory()->forClient($client)->sent()->create();
    $paid = Invoice::factory()->forClient($client)->paid()->create();

    Reminder::create([
        'invoice_id' => $open->id, 'type' => 'pre_due', 'offset_days' => -1,
        'scheduled_for' => now()->subHour(), 'status' => 'pending',
    ]);
    Reminder::create([
        'invoice_id' => $paid->id, 'type' => 'overdue', 'offset_days' => 1,
        'scheduled_for' => now()->subHour(), 'status' => 'pending',
    ]);

    $this->artisan('reminders:dispatch')->assertSuccessful();

    Mail::assertQueued(InvoiceReminderMail::class, 1);

    expect(Reminder::where('invoice_id', $open->id)->first()->status)->toBe('sent')
        ->and(Reminder::where('invoice_id', $paid->id)->first()->status)->toBe('skipped');
});

test('the mark-overdue command flags past-due open invoices', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();

    $overdue = Invoice::factory()->forClient($client)->sent()->create([
        'due_date' => now()->subDays(3)->toDateString(),
    ]);
    $current = Invoice::factory()->forClient($client)->sent()->create([
        'due_date' => now()->addDays(3)->toDateString(),
    ]);

    $this->artisan('invoices:mark-overdue')->assertSuccessful();

    expect($overdue->fresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and($current->fresh()->status)->toBe(InvoiceStatus::Sent);
});
