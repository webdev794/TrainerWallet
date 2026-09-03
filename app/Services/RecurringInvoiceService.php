<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceService
{
    public function __construct(
        private readonly InvoiceNumberService $numbers,
        private readonly InvoiceReminderService $reminders,
        private readonly InvoiceDocumentService $documents,
    ) {}

    /**
     * Generate invoices for every active schedule that is due, returning the count.
     */
    public function runDue(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();

        $schedules = RecurringInvoice::query()
            ->where('status', 'active')
            ->whereDate('next_run_at', '<=', $now->toDateString())
            ->with('trainer.trainerProfile', 'client')
            ->get();

        $generated = 0;

        foreach ($schedules as $schedule) {
            $this->generate($schedule, $now);
            $generated++;
        }

        return $generated;
    }

    public function generate(RecurringInvoice $schedule, ?CarbonImmutable $now = null): Invoice
    {
        $now ??= CarbonImmutable::now();
        $template = $schedule->template;

        $invoice = DB::transaction(function () use ($schedule, $template, $now): Invoice {
            $trainer = $schedule->trainer;

            $invoice = $trainer->invoices()->create([
                'client_id' => $schedule->client_id,
                'number' => $this->numbers->next($trainer->trainerProfile),
                'status' => InvoiceStatus::Draft,
                'currency' => $trainer->trainerProfile->currency,
                'discount_total' => $template['discount_total'] ?? 0,
                'tax_rate' => $template['tax_rate'] ?? 0,
                'due_date' => $now->addDays($schedule->due_days)->toDateString(),
                'notes' => $template['notes'] ?? null,
                'allowed_methods' => $template['allowed_methods'] ?? ['upi_manual'],
                'recurring_invoice_id' => $schedule->id,
            ]);

            foreach ($template['items'] ?? [] as $item) {
                $quantity = (float) ($item['quantity'] ?? 1);
                $unit = (float) ($item['unit_amount'] ?? 0);
                $invoice->items()->create([
                    'description' => $item['description'] ?? 'Item',
                    'quantity' => $quantity,
                    'unit_amount' => $unit,
                    'amount' => round($quantity * $unit, 2),
                ]);
            }

            $invoice->load('items');
            $invoice->recalculateTotals();
            $invoice->save();

            return $invoice;
        });

        $schedule->update([
            'last_generated_at' => $now->toDateString(),
            'next_run_at' => $schedule->advance()->toDateString(),
        ]);

        if ($schedule->auto_send) {
            $invoice->update([
                'status' => InvoiceStatus::Sent,
                'issued_at' => $now,
            ]);
            $this->reminders->schedule($invoice->fresh());
            $this->documents->emailInvoiceToClient($invoice->fresh());
        }

        return $invoice->fresh();
    }
}
