<?php

namespace App\Http\Controllers\Portal;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\InvoiceDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;

class PortalReceiptController extends Controller
{
    public function index(Request $request): \Inertia\Response
    {
        $clientIds = $request->user()->clientRecordIds();

        $receipts = $clientIds === [] ? collect() : Payment::query()
            ->whereHas('invoice', fn ($query) => $query->whereIn('client_id', $clientIds))
            ->where('status', PaymentStatus::Succeeded->value)
            ->with('invoice:id,number,currency')
            ->latest('paid_at')
            ->get()
            ->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'invoice_number' => $payment->invoice->number,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'method' => $payment->gateway->label(),
                'reference' => $payment->reference,
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ]);

        return Inertia::render('portal/receipts', [
            'receipts' => $receipts,
            'linked' => $clientIds !== [],
        ]);
    }

    public function download(Request $request, Payment $payment, InvoiceDocumentService $documents): Response
    {
        abort_unless(
            in_array($payment->invoice->client_id, $request->user()->clientRecordIds(), true)
            && $payment->status === PaymentStatus::Succeeded,
            404,
        );

        return $documents->paymentReceipt($payment)
            ->download("Receipt-{$payment->invoice->number}-{$payment->id}.pdf");
    }
}
