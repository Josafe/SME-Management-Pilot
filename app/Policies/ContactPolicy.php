<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

/**
 * Contacts follow the same rules as their client.
 */
class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role !== null;
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->role !== null;
    }

    public function create(User $user): bool
    {
        return $user->canManageClients();
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->canManageClients();
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->canManageClients();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canManageClients();
    }
}
