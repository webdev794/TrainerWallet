<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Mail\PaymentReceiptMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\InvoicePaidNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function seededInvoice(User $trainer, float $total = 2000): Invoice
{
    $client = Client::factory()->for($trainer, 'trainer')->create(['email' => 'client@example.com']);
    $invoice = Invoice::factory()->forClient($client)->sent()->create([
        'subtotal' => $total,
        'total' => $total,
    ]);
    $invoice->items()->create(['description' => 'x', 'quantity' => 1, 'unit_amount' => $total, 'amount' => $total]);

    return $invoice->fresh();
}

test('recording a full cash payment marks the invoice paid and notifies', function () {
    Notification::fake();
    Mail::fake();
    Storage::fake('local');

    $trainer = User::factory()->trainer()->create();
    $invoice = seededInvoice($trainer, 2000);

    $this->actingAs($trainer)->post(route('invoices.payments.store', $invoice), [
        'method' => 'cash',
        'amount' => 2000,
    ])->assertRedirect();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and((float) $invoice->amount_paid)->toBe(2000.0)
        ->and($invoice->paid_at)->not->toBeNull();

    Notification::assertSentTo($trainer, InvoicePaidNotification::class);
    Mail::assertSent(PaymentReceiptMail::class);
});

test('a partial payment leaves the invoice partially paid', function () {
    $trainer = User::factory()->trainer()->create();
    $invoice = seededInvoice($trainer, 2000);

    $this->actingAs($trainer)->post(route('invoices.payments.store', $invoice), [
        'method' => 'cash',
        'amount' => 800,
    ])->assertRedirect();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and($invoice->outstanding())->toBe(1200.0);
});

test('refunding the only payment reopens the invoice', function () {
    $trainer = User::factory()->trainer()->create();
    $invoice = seededInvoice($trainer, 2000);

    $this->actingAs($trainer)->post(route('invoices.payments.store', $invoice), [
        'method' => 'cash',
        'amount' => 2000,
    ]);

    $payment = $invoice->payments()->first();

    $this->actingAs($trainer)->post(route('payments.refund', $payment))->assertRedirect();

    $invoice->refresh();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and((float) $invoice->amount_paid)->toBe(0.0);
});

test('a client can submit a UPI reference which the trainer then confirms', function () {
    Notification::fake();
    Mail::fake();
    Storage::fake('local');

    $trainer = User::factory()->trainer()->create();
    $trainer->trainerProfile()->update(['upi_vpa' => 'coach@okhdfcbank']);
    $invoice = seededInvoice($trainer, 1500);
    $invoice->update(['allowed_methods' => ['upi_manual']]);

    // Public payer submits their UTR.
    $this->post(route('public-invoice.upi', $invoice->public_token), [
        'reference' => 'UTR123456789',
    ])->assertRedirect();

    $payment = Payment::first();
    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->reference)->toBe('UTR123456789');
    expect($invoice->fresh()->status)->not->toBe(InvoiceStatus::Paid);

    // Trainer confirms receipt.
    $this->actingAs($trainer)->post(route('payments.confirm', $payment))->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('a trainer cannot record a payment on another trainers invoice', function () {
    $trainer = User::factory()->trainer()->create();
    $invoice = Invoice::factory()->sent()->create();

    $this->actingAs($trainer)
        ->post(route('invoices.payments.store', $invoice), ['method' => 'cash', 'amount' => 100])
        ->assertForbidden();
});
