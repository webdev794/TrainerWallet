<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $clientRecord = $request->user()->clientRecord;

        $invoices = $clientRecord === null ? collect() : Invoice::query()
            ->where('client_id', $clientRecord->id)
            ->whereNot('status', InvoiceStatus::Draft->value)
            ->latest()
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'total' => $invoice->total,
                'outstanding' => $invoice->outstanding(),
                'currency' => $invoice->currency,
                'due_date' => $invoice->due_date?->toDateString(),
            ]);

        return Inertia::render('portal/index', [
            'invoices' => $invoices,
        ]);
    }
}
