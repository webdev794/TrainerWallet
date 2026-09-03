<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTrainer();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->trainer_id;
    }

    public function create(User $user): bool
    {
        return $user->isTrainer();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->trainer_id && $invoice->isEditable();
    }

    public function send(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->trainer_id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->trainer_id && $invoice->isEditable();
    }
}
