<?php

namespace App\Services;

use App\Enums\ReminderType;
use App\Models\Invoice;
use Carbon\CarbonImmutable;

class InvoiceReminderService
{
    /**
     * Materialise the reminder schedule for an invoice from config offsets.
     * Past-dated reminders are recorded as skipped so they never fire late.
     */
    public function schedule(Invoice $invoice): void
    {
        if ($invoice->due_date === null) {
            return;
        }

        $due = CarbonImmutable::parse($invoice->due_date)->startOfDay();
        $offsets = config('coachpay.reminder_offsets', []);

        foreach ([ReminderType::PreDue, ReminderType::Overdue] as $type) {
            foreach ($offsets[$type->value] ?? [] as $offset) {
                $when = $due->addDays((int) $offset)->setTime(9, 0);

                $invoice->reminders()->updateOrCreate(
                    ['type' => $type->value, 'offset_days' => (int) $offset],
                    [
                        'channel' => 'mail',
                        'scheduled_for' => $when,
                        'status' => $when->isPast() ? 'skipped' : 'pending',
                    ],
                );
            }
        }
    }
}
