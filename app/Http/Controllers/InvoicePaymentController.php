<?php

namespace App\Http\Controllers;

use App\Enums\PaymentGatewayType;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoiceDocumentService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InvoicePaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $payments = Payment::query()
            ->where('trainer_id', $request->user()->id)
            ->with('invoice:id,number,client_id', 'invoice.client:id,name')
            ->latest()
            ->paginate(20)
            ->through(fn (Payment $payment): array => [
                'id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'invoice_number' => $payment->invoice->number,
                'client_name' => $payment->invoice->client->name,
                'gateway_label' => $payment->gateway->label(),
                'amount' => $payment->amount,
                'net_amount' => $payment->net_amount,
                'currency' => $payment->currency,
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'reference' => $payment->reference,
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'created_at' => $payment->created_at?->toIso8601String(),
            ]);

        return Inertia::render('payments/index', [
            'payments' => $payments,
            'currency' => $request->user()->trainerProfile()->value('currency'),
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('send', $invoice);

        $data = $request->validate([
            'method' => ['required', Rule::in([PaymentGatewayType::Cash->value, PaymentGatewayType::UpiManual->value])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:100000000'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $this->payments->recordManualPayment(
            $invoice,
            PaymentGatewayType::from($data['method']),
            (float) $data['amount'],
            $data['reference'] ?? null,
            confirmed: true,
        );

        return back()->with('status', 'Payment recorded.');
    }

    public function confirm(Payment $payment): RedirectResponse
    {
        $this->authorize('send', $payment->invoice);

        $this->payments->confirmPayment($payment);

        return back()->with('status', 'Payment confirmed.');
    }

    public function refund(Payment $payment): RedirectResponse
    {
        $this->authorize('send', $payment->invoice);

        $this->payments->refundPayment($payment);

        return back()->with('status', 'Payment refunded.');
    }

    public function receipt(Payment $payment, InvoiceDocumentService $documents): \Illuminate\Http\Response
    {
        $this->authorize('view', $payment->invoice);

        abort_unless($payment->status === PaymentStatus::Succeeded, 404);

        return $documents->paymentReceipt($payment)
            ->download("Receipt-{$payment->invoice->number}-{$payment->id}.pdf");
    }
}
