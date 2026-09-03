<?php

namespace App\Console\Commands;

use App\Services\RecurringInvoiceService;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';

    protected $description = 'Generate invoices from active recurring schedules that are due';

    public function handle(RecurringInvoiceService $service): int
    {
        $count = $service->runDue();

        $this->info("Generated {$count} recurring invoice(s).");

        return self::SUCCESS;
    }
}
