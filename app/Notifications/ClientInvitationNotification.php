<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public string $trainerName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject("{$this->trainerName} invited you to CoachPay")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$this->trainerName} uses CoachPay to send invoices and receipts.")
            ->line('Set a password to access your client portal, where you can view invoices, pay them, and download receipts.')
            ->action('Set your password', $url)
            ->line('This link will expire in 60 minutes.');
    }
}
