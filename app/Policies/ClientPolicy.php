<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTrainer();
    }

    public function view(User $user, Client $client): bool
    {
        return $user->id === $client->trainer_id;
    }

    public function create(User $user): bool
    {
        return $user->isTrainer();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->id === $client->trainer_id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->id === $client->trainer_id;
    }
}
