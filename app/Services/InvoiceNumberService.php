<?php

namespace App\Services;

use App\Models\TrainerProfile;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    /**
     * Atomically consume and return the next invoice number for a trainer.
     */
    public function next(TrainerProfile $profile): string
    {
        return DB::transaction(function () use ($profile): string {
            /** @var TrainerProfile $locked */
            $locked = TrainerProfile::query()->lockForUpdate()->findOrFail($profile->id);

            $number = $locked->next_invoice_number;
            $locked->update(['next_invoice_number' => $number + 1]);

            return $locked->invoice_prefix.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
        });
    }
}
