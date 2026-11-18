<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

/**
 * Addresses follow the same rules as their client.
 */
class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== null;
    }

    public function view(User $user, Address $address): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->canManageClients();
    }

    public function update(User $user, Address $address): bool
    {
        return $user->canManageClients();
    }

    public function delete(User $user, Address $address): bool
    {
        return $user->canManageClients();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canManageClients();
    }
}
