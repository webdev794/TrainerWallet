<?php

namespace App\Policies;

use App\Models\RecurringInvoice;
use App\Models\User;

class RecurringInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTrainer();
    }

    public function create(User $user): bool
    {
        return $user->isTrainer();
    }

    public function update(User $user, RecurringInvoice $schedule): bool
    {
        return $user->id === $schedule->trainer_id;
    }

    public function delete(User $user, RecurringInvoice $schedule): bool
    {
        return $user->id === $schedule->trainer_id;
    }
}
