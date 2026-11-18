<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * All roles may view clients; only roles that manage clients may change them.
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== null;
    }

    public function view(User $user, Client $client): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->canManageClients();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->canManageClients();
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->canManageClients();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canManageClients();
    }
}
