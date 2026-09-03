<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\ReminderType;
use App\Mail\InvoiceReminderMail;
use App\Models\Reminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DispatchReminders extends Command
{
    protected $signature = 'reminders:dispatch';

    protected $description = 'Send due invoice reminders and dunning emails';

    public function handle(): int
    {
        $reminders = Reminder::query()
            ->where('status', 'pending')
            ->where('scheduled_for', '<=', CarbonImmutable::now())
            ->with('invoice.client', 'invoice.trainer.trainerProfile')
            ->get();

        $sent = 0;

        foreach ($reminders as $reminder) {
            $invoice = $reminder->invoice;

            if (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Void], true)) {
                $reminder->update(['status' => 'skipped']);

                continue;
            }

            if ($invoice->client->email !== null) {
                Mail::to($invoice->client->email)->queue(
                    new InvoiceReminderMail($invoice, overdue: $reminder->type === ReminderType::Overdue),
                );
                $sent++;
            }

            $reminder->update(['status' => 'sent', 'sent_at' => CarbonImmutable::now()]);
        }

        $this->info("Dispatched {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
