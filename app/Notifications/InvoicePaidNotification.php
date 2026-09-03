<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaidNotification extends Notification
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Payment received for {$this->invoice->number}")
            ->greeting("Nice, {$notifiable->name}!")
            ->line("{$this->invoice->client->name} paid invoice {$this->invoice->number}.")
            ->line('Amount: '.number_format((float) $this->invoice->total, 2).' '.$this->invoice->currency)
            ->action('View invoice', route('invoices.show', $this->invoice));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->number,
            'client_name' => $this->invoice->client->name,
            'amount' => $this->invoice->total,
            'currency' => $this->invoice->currency,
        ];
    }
}
