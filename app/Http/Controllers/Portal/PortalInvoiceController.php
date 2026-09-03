<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Payments\PublicInvoiceController;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalInvoiceController extends Controller
{
    public function __construct(private readonly PublicInvoiceController $publicInvoices) {}

    public function show(Request $request, Invoice $invoice): Response
    {
        $clientRecord = $request->user()->clientRecord;

        abort_if($clientRecord === null || $invoice->client_id !== $clientRecord->id, 403);

        $invoice->load(['items', 'client:id,name,email', 'trainer.trainerProfile', 'payments' => fn ($q) => $q->latest()]);

        return Inertia::render('invoice/public', [
            'invoice' => $this->publicInvoices->present($invoice),
            'context' => 'portal',
        ]);
    }
}
