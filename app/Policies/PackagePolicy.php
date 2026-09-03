<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

class PackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTrainer();
    }

    public function create(User $user): bool
    {
        return $user->isTrainer();
    }

    public function update(User $user, Package $package): bool
    {
        return $user->id === $package->trainer_id;
    }

    public function delete(User $user, Package $package): bool
    {
        return $user->id === $package->trainer_id;
    }
}
