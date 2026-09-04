<?php

namespace App\Services;

use App\Mail\InvoiceSentMail;
use App\Mail\PaymentReceiptMail;
use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceDocumentService
{
    public function __construct(private readonly UpiQrService $upiQr) {}

    /**
     * Render (or re-render) the invoice PDF and return its stored path.
     */
    public function generateInvoice(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'client', 'trainer.trainerProfile']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'profile' => $invoice->trainer->trainerProfile,
            'upiQr' => $this->upiQr->dataUri($invoice),
        ]);

        $path = "invoices/{$invoice->public_token}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    public function generateReceipt(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'payments', 'client', 'trainer.trainerProfile']);

        $pdf = Pdf::loadView('pdf.receipt', [
            'invoice' => $invoice,
            'profile' => $invoice->trainer->trainerProfile,
        ]);

        $path = "receipts/{$invoice->public_token}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Render a receipt PDF for a single payment (not persisted).
     */
    public function paymentReceipt(Payment $payment): PdfInstance
    {
        $payment->loadMissing(['invoice.client', 'invoice.trainer.trainerProfile']);

        return Pdf::loadView('pdf.payment-receipt', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
            'profile' => $payment->invoice->trainer->trainerProfile,
        ]);
    }

    public function emailInvoiceToClient(Invoice $invoice): void
    {
        $email = $invoice->client->email;

        if ($email === null) {
            return;
        }

        $this->generateInvoice($invoice);

        Mail::to($email)->send(new InvoiceSentMail($invoice));
    }

    public function emailReceiptToClient(Invoice $invoice): void
    {
        $email = $invoice->client->email;

        if ($email === null) {
            return;
        }

        Mail::to($email)->send(new PaymentReceiptMail($invoice));
    }
}
