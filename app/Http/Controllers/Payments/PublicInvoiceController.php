<?php

namespace App\Http\Controllers\Payments;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\UpiQrService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicInvoiceController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly UpiQrService $upiQr,
    ) {}

    public function show(Request $request, string $token): Response
    {
        $invoice = Invoice::query()
            ->where('public_token', $token)
            ->with(['items', 'client:id,name,email', 'trainer.trainerProfile', 'payments' => fn ($q) => $q->latest()])
            ->firstOrFail();

        abort_if($invoice->status === InvoiceStatus::Draft, 404);

        if ($invoice->status === InvoiceStatus::Sent) {
            $invoice->update(['status' => InvoiceStatus::Viewed, 'viewed_at' => CarbonImmutable::now()]);
        }

        return Inertia::render('invoice/public', [
            'invoice' => $this->present($invoice),
            'context' => 'public',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Invoice $invoice): array
    {
        $profile = $invoice->trainer->trainerProfile;
        $configured = $profile !== null ? $this->gateways->availableFor($profile) : [];
        $configuredValues = array_map(fn ($type): string => $type->value, $configured);

        $allowed = $invoice->allowed_methods ?? [];
        $methods = array_values(array_filter(
            $configuredValues,
            fn (string $value): bool => $allowed === [] || in_array($value, $allowed, true),
        ));

        return [
            'token' => $invoice->public_token,
            'number' => $invoice->number,
            'business_name' => $profile->business_name ?? $invoice->trainer->name,
            'status' => $invoice->status->value,
            'status_label' => $invoice->status->label(),
            'currency' => $invoice->currency,
            'total' => $invoice->total,
            'amount_paid' => $invoice->amount_paid,
            'outstanding' => $invoice->outstanding(),
            'due_date' => $invoice->due_date?->toDateString(),
            'is_payable' => $invoice->isPayable(),
            'client_name' => $invoice->client->name,
            'notes' => $invoice->notes,
            'items' => $invoice->items->map(fn ($item): array => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_amount' => $item->unit_amount,
                'amount' => $item->amount,
            ])->values(),
            'methods' => $methods,
            'upi_intent' => in_array('upi_manual', $methods, true) ? $this->upiQr->intentUri($invoice) : null,
            'upi_qr' => in_array('upi_manual', $methods, true) ? $this->upiQr->dataUri($invoice) : null,
            'payments' => $invoice->payments
                ->where('status', '!=', 'pending')
                ->map(fn ($payment): array => [
                    'gateway_label' => $payment->gateway->label(),
                    'amount' => $payment->amount,
                    'status_label' => $payment->status->label(),
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ])->values(),
        ];
    }
}
