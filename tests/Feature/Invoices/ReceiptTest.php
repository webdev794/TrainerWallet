<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;

function paidInvoiceWithPayment(User $trainer, ?Client $client = null): Payment
{
    $client ??= Client::factory()->for($trainer, 'trainer')->create();
    $invoice = Invoice::factory()->forClient($client)->paid()->create();
    $invoice->items()->create(['description' => 'x', 'quantity' => 1, 'unit_amount' => 2000, 'amount' => 2000]);

    return Payment::factory()->succeeded()->create([
        'invoice_id' => $invoice->id,
        'trainer_id' => $trainer->id,
        'amount' => 2000,
    ]);
}

test('the portal receipts page lists a receipt per succeeded payment', function () {
    $trainer = User::factory()->trainer()->create();
    $clientUser = User::factory()->client()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create(['client_user_id' => $clientUser->id]);

    paidInvoiceWithPayment($trainer, $client);
    paidInvoiceWithPayment($trainer, $client);
    Payment::factory()->create([  // pending — should not appear
        'invoice_id' => Invoice::factory()->forClient($client)->create()->id,
        'trainer_id' => $trainer->id,
    ]);

    $this->actingAs($clientUser)
        ->get(route('portal.receipts'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('portal/receipts')
            ->has('receipts', 2));
});

test('a client can download a receipt pdf for their own payment', function () {
    $trainer = User::factory()->trainer()->create();
    $clientUser = User::factory()->client()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create(['client_user_id' => $clientUser->id]);
    $payment = paidInvoiceWithPayment($trainer, $client);

    $this->actingAs($clientUser)
        ->get(route('portal.receipts.download', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('a client cannot download another clients receipt', function () {
    $trainer = User::factory()->trainer()->create();
    $clientUser = User::factory()->client()->create();
    Client::factory()->for($trainer, 'trainer')->create(['client_user_id' => $clientUser->id]);

    $foreignPayment = paidInvoiceWithPayment($trainer);

    $this->actingAs($clientUser)
        ->get(route('portal.receipts.download', $foreignPayment))
        ->assertNotFound();
});

test('a trainer can download a payment receipt from an invoice', function () {
    $trainer = User::factory()->trainer()->create();
    $payment = paidInvoiceWithPayment($trainer);

    $this->actingAs($trainer)
        ->get(route('payments.receipt', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('a receipt cannot be pulled for a pending payment', function () {
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create();
    $invoice = Invoice::factory()->forClient($client)->sent()->create();
    $pending = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'trainer_id' => $trainer->id,
    ]);

    $this->actingAs($trainer)
        ->get(route('payments.receipt', $pending))
        ->assertNotFound();
});
