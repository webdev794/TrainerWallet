<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentGatewayType;
use App\Enums\PaymentStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Notification::fake();
    Mail::fake();
    Storage::fake('local');
});

function pendingOnlinePayment(PaymentGatewayType $gateway, string $orderId, float $total = 2000): Payment
{
    $trainer = User::factory()->trainer()->create();
    $client = Client::factory()->for($trainer, 'trainer')->create(['email' => 'c@example.com']);
    $invoice = Invoice::factory()->forClient($client)->sent()->create([
        'subtotal' => $total, 'total' => $total,
    ]);
    $invoice->items()->create(['description' => 'x', 'quantity' => 1, 'unit_amount' => $total, 'amount' => $total]);

    return $invoice->payments()->create([
        'trainer_id' => $trainer->id,
        'gateway' => $gateway,
        'gateway_order_id' => $orderId,
        'amount' => $total,
        'currency' => 'INR',
        'net_amount' => $total,
        'status' => PaymentStatus::Pending,
        'idempotency_key' => Str::uuid()->toString(),
    ]);
}

test('a stripe checkout.session.completed webhook settles the invoice', function () {
    $payment = pendingOnlinePayment(PaymentGatewayType::Stripe, 'cs_test_123', 2000);

    $payload = [
        'id' => 'evt_1',
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_test_123',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_1',
            'amount_total' => 200000,
        ]],
    ];

    $this->postJson(route('webhooks.stripe'), $payload)->assertNoContent();

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Succeeded)
        ->and($payment->gateway_payment_id)->toBe('pi_test_1')
        ->and($payment->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('a duplicate stripe webhook delivery is a no-op', function () {
    $payment = pendingOnlinePayment(PaymentGatewayType::Stripe, 'cs_test_dupe', 2000);

    $payload = [
        'id' => 'evt_dupe',
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_test_dupe',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_dupe',
            'amount_total' => 200000,
        ]],
    ];

    $this->postJson(route('webhooks.stripe'), $payload)->assertNoContent();
    $this->postJson(route('webhooks.stripe'), $payload)->assertNoContent();

    expect(Payment::where('gateway_order_id', 'cs_test_dupe')->where('status', PaymentStatus::Succeeded->value)->count())->toBe(1)
        ->and(WebhookEvent::where('event_id', 'evt_dupe')->count())->toBe(1);
});

test('a paypal capture completed webhook settles the invoice', function () {
    $payment = pendingOnlinePayment(PaymentGatewayType::PayPal, 'PPORDER123', 3000);

    $payload = [
        'id' => 'WH-1',
        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
        'resource' => [
            'id' => 'CAPTURE-1',
            'amount' => ['value' => '3000.00', 'currency_code' => 'INR'],
            'supplementary_data' => ['related_ids' => ['order_id' => 'PPORDER123']],
        ],
    ];

    $this->postJson(route('webhooks.paypal'), $payload)->assertNoContent();

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Succeeded)
        ->and($payment->invoice->fresh()->status)->toBe(InvoiceStatus::Paid);
});

test('a paypal refund webhook marks the payment refunded', function () {
    $payment = pendingOnlinePayment(PaymentGatewayType::PayPal, 'PPORDER-R', 1000);
    $payment->update([
        'status' => PaymentStatus::Succeeded,
        'gateway_payment_id' => 'CAP-R',
        'paid_at' => now(),
    ]);
    app(PaymentService::class)->settleInvoice($payment->invoice->fresh());

    $payload = [
        'id' => 'WH-R',
        'event_type' => 'PAYMENT.CAPTURE.REFUNDED',
        'resource' => [
            'id' => 'CAP-R',
            'amount' => ['value' => '1000.00', 'currency_code' => 'INR'],
        ],
    ];

    $this->postJson(route('webhooks.paypal'), $payload)->assertNoContent();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded)
        ->and($payment->invoice->fresh()->status)->not->toBe(InvoiceStatus::Paid);
});
