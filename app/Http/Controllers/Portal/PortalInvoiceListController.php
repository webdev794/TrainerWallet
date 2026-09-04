<?php

namespace App\Http\Controllers\Portal;

use App\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalInvoiceListController extends Controller
{
    public function index(Request $request): Response
    {
        $clientIds = $request->user()->clientRecordIds();

        $invoices = $clientIds === [] ? collect() : Invoice::query()
            ->whereIn('client_id', $clientIds)
            ->whereNot('status', InvoiceStatus::Draft->value)
            ->withCount('items')
            ->with('trainer:id,name')
            ->latest()
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'trainer_name' => $invoice->trainer->name,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'total' => $invoice->total,
                'outstanding' => $invoice->outstanding(),
                'currency' => $invoice->currency,
                'due_date' => $invoice->due_date?->toDateString(),
                'items_count' => $invoice->items_count,
            ]);

        return Inertia::render('portal/invoices', [
            'invoices' => $invoices,
            'linked' => $clientIds !== [],
        ]);
    }
}
