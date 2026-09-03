<?php

namespace App\Policies;

use App\Models\TrainingSession;
use App\Models\User;

class TrainingSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTrainer();
    }

    public function view(User $user, TrainingSession $session): bool
    {
        return $user->id === $session->trainer_id;
    }

    public function create(User $user): bool
    {
        return $user->isTrainer();
    }

    public function update(User $user, TrainingSession $session): bool
    {
        return $user->id === $session->trainer_id;
    }

    public function delete(User $user, TrainingSession $session): bool
    {
        return $user->id === $session->trainer_id;
    }
}
