<?php

namespace App\Services\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentGatewayType;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Notifications\InvoicePaidNotification;
use App\Services\InvoiceDocumentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly InvoiceDocumentService $documents,
    ) {}

    /**
     * Start an online (redirect) checkout for an invoice.
     *
     * @param  \Closure(Payment): array{0: string, 1: string}  $urls  Returns [successUrl, cancelUrl] for the created payment.
     */
    public function beginCheckout(Invoice $invoice, PaymentGatewayType $type, \Closure $urls): GatewayCheckout
    {
        if (! $invoice->isPayable()) {
            throw new RuntimeException('This invoice is not open for payment.');
        }

        $this->assertMethodAllowed($invoice, $type);

        $gateway = $this->gateways->for($type);

        if ($invoice->trainer->trainerProfile !== null && ! $gateway->isConfiguredFor($invoice->trainer->trainerProfile)) {
            throw new RuntimeException($type->label().' is not connected for this trainer.');
        }

        $payment = $invoice->payments()->create([
            'trainer_id' => $invoice->trainer_id,
            'gateway' => $type,
            'amount' => $invoice->outstanding(),
            'currency' => $invoice->currency,
            'net_amount' => $invoice->outstanding(),
            'status' => PaymentStatus::Pending,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        [$successUrl, $cancelUrl] = $urls($payment);

        $checkout = $gateway->createCheckout($invoice, $payment, $successUrl, $cancelUrl);

        $payment->update(['gateway_order_id' => $checkout->gatewayOrderId]);

        return $checkout;
    }

    /**
     * Confirm a redirect-flow payment after the payer returns.
     */
    public function completeReturn(Payment $payment): bool
    {
        if ($payment->status === PaymentStatus::Succeeded) {
            return true;
        }

        $result = $this->gateways->for($payment->gateway)->confirmReturn($payment);

        if ($result->outcome === GatewayWebhookResult::OUTCOME_PAID) {
            $this->markSucceeded($payment, $result);

            return true;
        }

        return false;
    }

    /**
     * Handle an inbound gateway webhook idempotently.
     */
    public function handleWebhook(PaymentGatewayType $type, Request $request): void
    {
        $result = $this->gateways->for($type)->parseWebhook($request);

        if ($result === null || $result->isIgnored()) {
            return;
        }

        $event = WebhookEvent::firstOrCreate(
            ['gateway' => $type->value, 'event_id' => $result->eventId],
            ['type' => $result->type, 'payload' => $result->payload],
        );

        if ($event->processed_at !== null) {
            return;
        }

        $this->applyResult($type, $result);

        $event->update(['processed_at' => CarbonImmutable::now()]);
    }

    /**
     * Record a cash or UPI payment entered by hand.
     */
    public function recordManualPayment(
        Invoice $invoice,
        PaymentGatewayType $type,
        float $amount,
        ?string $reference,
        bool $confirmed,
    ): Payment {
        $payment = $invoice->payments()->create([
            'trainer_id' => $invoice->trainer_id,
            'gateway' => $type,
            'amount' => $amount,
            'currency' => $invoice->currency,
            'net_amount' => $amount,
            'reference' => $reference,
            'method_detail' => $type->label(),
            'status' => $confirmed ? PaymentStatus::Succeeded : PaymentStatus::Pending,
            'paid_at' => $confirmed ? CarbonImmutable::now() : null,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        if ($confirmed) {
            $this->settleInvoice($invoice->fresh());
        }

        return $payment;
    }

    public function confirmPayment(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::Succeeded) {
            return;
        }

        $payment->update([
            'status' => PaymentStatus::Succeeded,
            'paid_at' => CarbonImmutable::now(),
        ]);

        $this->settleInvoice($payment->invoice->fresh());
    }

    public function refundPayment(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::Succeeded) {
            throw new RuntimeException('Only successful payments can be refunded.');
        }

        if ($payment->gateway->isOnline()) {
            $this->gateways->for($payment->gateway)->refund($payment);
        }

        $payment->update(['status' => PaymentStatus::Refunded]);

        $this->settleInvoice($payment->invoice->fresh());
    }

    private function applyResult(PaymentGatewayType $type, GatewayWebhookResult $result): void
    {
        $payment = $this->locatePayment($type, $result);

        if ($payment === null) {
            return;
        }

        match ($result->outcome) {
            GatewayWebhookResult::OUTCOME_PAID => $this->markSucceeded($payment, $result),
            GatewayWebhookResult::OUTCOME_REFUNDED => $this->markRefunded($payment),
            default => null,
        };
    }

    private function locatePayment(PaymentGatewayType $type, GatewayWebhookResult $result): ?Payment
    {
        return Payment::query()
            ->where('gateway', $type->value)
            ->where(function ($query) use ($result): void {
                if ($result->gatewayOrderId !== null) {
                    $query->orWhere('gateway_order_id', $result->gatewayOrderId);
                }
                if ($result->gatewayPaymentId !== null) {
                    $query->orWhere('gateway_payment_id', $result->gatewayPaymentId);
                }
            })
            ->latest('id')
            ->first();
    }

    private function markSucceeded(Payment $payment, GatewayWebhookResult $result): void
    {
        if ($payment->status === PaymentStatus::Succeeded) {
            return;
        }

        $amount = $result->amountMinor !== null
            ? Money::toMajor($result->amountMinor)
            : (float) $payment->amount;
        $fee = $result->feeMinor !== null ? Money::toMajor($result->feeMinor) : 0.0;

        $payment->update([
            'status' => PaymentStatus::Succeeded,
            'gateway_payment_id' => $result->gatewayPaymentId ?? $payment->gateway_payment_id,
            'amount' => $amount,
            'fee_amount' => $fee,
            'net_amount' => round($amount - $fee, 2),
            'paid_at' => CarbonImmutable::now(),
            'raw_payload' => $result->payload,
        ]);

        $this->settleInvoice($payment->invoice->fresh());
    }

    private function markRefunded(Payment $payment): void
    {
        if ($payment->status === PaymentStatus::Refunded) {
            return;
        }

        $payment->update(['status' => PaymentStatus::Refunded]);
        $this->settleInvoice($payment->invoice->fresh());
    }

    /**
     * Roll succeeded payments up onto the invoice and fire side effects on
     * the transition into a fully paid state.
     */
    public function settleInvoice(Invoice $invoice): void
    {
        $wasPaid = $invoice->status === InvoiceStatus::Paid;

        $paid = (float) $invoice->payments()
            ->where('status', PaymentStatus::Succeeded->value)
            ->sum('amount');

        $invoice->amount_paid = (string) round($paid, 2);

        if ($invoice->isFullyPaid()) {
            $invoice->status = InvoiceStatus::Paid;
            $invoice->paid_at ??= CarbonImmutable::now();
        } elseif ($paid > 0) {
            $invoice->status = InvoiceStatus::PartiallyPaid;
            $invoice->paid_at = null;
        } elseif (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::PartiallyPaid], true)) {
            $invoice->status = $invoice->issued_at !== null ? InvoiceStatus::Sent : InvoiceStatus::Draft;
            $invoice->paid_at = null;
        }

        $invoice->save();

        if (! $wasPaid && $invoice->status === InvoiceStatus::Paid) {
            DB::afterCommit(function () use ($invoice): void {
                $this->documents->generateReceipt($invoice->fresh());
                $invoice->trainer->notify(new InvoicePaidNotification($invoice));
                $this->documents->emailReceiptToClient($invoice->fresh());
            });
        }
    }

    private function assertMethodAllowed(Invoice $invoice, PaymentGatewayType $type): void
    {
        $allowed = $invoice->allowed_methods ?? [];

        if ($allowed !== [] && ! in_array($type->value, $allowed, true)) {
            throw new RuntimeException($type->label().' is not enabled for this invoice.');
        }
    }
}
