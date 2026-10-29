<?php

namespace App\Policies;

use App\Enums\MembershipRole;
use App\Models\Church;
use App\Models\User;

class ChurchPolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isCentralAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->churchMemberships()->active()->exists();
    }

    public function view(User $user, Church $church): bool
    {
        return $user->churchMemberships()
            ->active()
            ->where('church_id', $church->id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return false; // Only Central Admin (handled in before)
    }

    public function update(User $user, Church $church): bool
    {
        return $user->hasRoleInChurch($church, MembershipRole::ChurchAdmin);
    }

    public function manage(User $user, Church $church): bool
    {
        return $user->hasRoleInChurch($church, MembershipRole::ChurchAdmin);
    }

    public function verify(User $user, Church $church): bool
    {
        return false; // Only Central Admin
    }

    public function publish(User $user, Church $church): bool
    {
        return false; // Only Central Admin
    }

    public function suspend(User $user, Church $church): bool
    {
        return false; // Only Central Admin
    }

    public function deactivate(User $user, Church $church): bool
    {
        return false; // Only Central Admin
    }
}
