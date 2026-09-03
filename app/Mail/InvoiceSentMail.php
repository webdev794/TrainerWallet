<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InvoiceSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $business = $this->invoice->trainer->trainerProfile()->value('business_name')
            ?? $this->invoice->trainer->name;

        return new Envelope(
            subject: "Invoice {$this->invoice->number} from {$business}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-sent',
            with: [
                'invoice' => $this->invoice,
                'payUrl' => route('public-invoice.show', $this->invoice->public_token),
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if ($this->invoice->pdf_path === null || ! Storage::disk('local')->exists($this->invoice->pdf_path)) {
            return [];
        }

        return [
            \Illuminate\Mail\Mailables\Attachment::fromStorageDisk('local', $this->invoice->pdf_path)
                ->as("Invoice-{$this->invoice->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
