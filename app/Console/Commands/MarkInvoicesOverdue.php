<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class MarkInvoicesOverdue extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Flag open invoices past their due date as overdue';

    public function handle(): int
    {
        $count = Invoice::query()
            ->whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Viewed->value,
                InvoiceStatus::PartiallyPaid->value,
            ])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', CarbonImmutable::now()->toDateString())
            ->update(['status' => InvoiceStatus::Overdue->value]);

        $this->info("Marked {$count} invoice(s) overdue.");

        return self::SUCCESS;
    }
}
