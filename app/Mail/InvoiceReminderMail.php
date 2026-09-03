<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public bool $overdue = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->overdue
                ? "Overdue: invoice {$this->invoice->number}"
                : "Reminder: invoice {$this->invoice->number} is due soon",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invoice-reminder',
            with: [
                'invoice' => $this->invoice,
                'overdue' => $this->overdue,
                'payUrl' => route('public-invoice.show', $this->invoice->public_token),
            ],
        );
    }
}
