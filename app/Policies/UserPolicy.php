<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $actor, string $ability): ?bool
    {
        if ($actor->isCentralAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $actor): bool
    {
        return $actor->isCentralAdmin();
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->isCentralAdmin() || $actor->id === $user->id;
    }

    public function create(User $actor): bool
    {
        return $actor->isCentralAdmin();
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->isCentralAdmin();
    }

    public function delete(User $actor, User $user): bool
    {
        return $actor->isCentralAdmin();
    }

    public function toggleStatus(User $actor, User $user): bool
    {
        return $actor->isCentralAdmin();
    }

    public function manageRoles(User $actor, User $user): bool
    {
        return $actor->isCentralAdmin();
    }
}
