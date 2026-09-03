<?php

use App\Enums\InvoiceStatus;
use App\Enums\SessionStatus;
use App\Mail\InvoiceSentMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

test('a trainer can create a draft invoice with computed totals', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();

    $this->actingAs($trainer)->post(route('invoices.store'), [
        'client_id' => $client->id,
        'due_date' => now()->addDays(7)->toDateString(),
        'discount_total' => 100,
        'tax_rate' => 10,
        'allowed_methods' => ['upi_manual', 'stripe'],
        'items' => [
            ['description' => 'Session', 'quantity' => 2, 'unit_amount' => 1000],
            ['description' => 'Assessment', 'quantity' => 1, 'unit_amount' => 500],
        ],
    ])->assertRedirect();

    $invoice = Invoice::first();

    expect($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and((float) $invoice->subtotal)->toBe(2500.0)
        ->and((float) $invoice->tax_total)->toBe(240.0)   // (2500 - 100) * 10%
        ->and((float) $invoice->total)->toBe(2640.0)
        ->and($invoice->number)->toStartWith($trainer->trainerProfile->invoice_prefix);
});

test('creating an invoice from a completed session links that session', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    $session = TrainingSession::factory()->forClient($client)->completed()->create(['rate' => 1200]);

    $this->actingAs($trainer)->post(route('invoices.store'), [
        'client_id' => $client->id,
        'items' => [[
            'description' => 'Session',
            'quantity' => 1,
            'unit_amount' => 1200,
            'training_session_id' => $session->id,
        ]],
    ])->assertRedirect();

    expect($session->fresh()->invoice_id)->toBe(Invoice::first()->id);
});

test('sending an invoice issues it and emails the client', function () {
    Mail::fake();
    Storage::fake('local');

    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create(['email' => 'client@example.com']);
    $invoice = Invoice::factory()->forClient($client)->create();
    $invoice->items()->create(['description' => 'x', 'quantity' => 1, 'unit_amount' => 1000, 'amount' => 1000]);

    $this->actingAs($trainer)->post(route('invoices.send', $invoice))->assertRedirect();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and($invoice->issued_at)->not->toBeNull();

    Mail::assertSent(InvoiceSentMail::class);
});

test('voiding an invoice releases its linked sessions', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    $invoice = Invoice::factory()->forClient($client)->sent()->create();
    $session = TrainingSession::factory()->forClient($client)->completed()->create(['invoice_id' => $invoice->id]);

    $this->actingAs($trainer)->post(route('invoices.void', $invoice))->assertRedirect();

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Void)
        ->and($session->fresh()->invoice_id)->toBeNull();
});

test('the invoice pdf renders and downloads', function () {
    Storage::fake('local');

    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    $invoice = Invoice::factory()->forClient($client)->sent()->create();
    $invoice->items()->create(['description' => 'x', 'quantity' => 1, 'unit_amount' => 1000, 'amount' => 1000]);

    $this->actingAs($trainer)
        ->get(route('invoices.pdf', $invoice))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('a trainer cannot view another trainers invoice', function () {
    $trainer = User::factory()->trainer()->create();
    $invoice = Invoice::factory()->create();

    $this->actingAs($trainer)->get(route('invoices.show', $invoice))->assertForbidden();
});

test('a sent invoice can no longer be edited', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    $invoice = Invoice::factory()->forClient($client)->sent()->create();

    $this->actingAs($trainer)
        ->put(route('invoices.update', $invoice), [
            'client_id' => $client->id,
            'items' => [['description' => 'x', 'quantity' => 1, 'unit_amount' => 1]],
        ])
        ->assertForbidden();
});
