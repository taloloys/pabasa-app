<?php

namespace App\Policies;

use App\Enums\MembershipRole;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\User;

class ChurchMembershipPolicy
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

    public function viewAny(User $user, Church $church): bool
    {
        return $user->hasRoleInChurch($church, MembershipRole::ChurchAdmin);
    }

    public function view(User $user, ChurchMembership $membership): bool
    {
        if ($user->id === $membership->user_id) {
            return true;
        }

        return $user->hasRoleInChurch($membership->church_id, MembershipRole::ChurchAdmin);
    }

    public function create(User $user, Church $church): bool
    {
        return $user->hasRoleInChurch($church, MembershipRole::ChurchAdmin);
    }

    public function revoke(User $user, ChurchMembership $membership): bool
    {
        return $user->hasRoleInChurch($membership->church_id, MembershipRole::ChurchAdmin);
    }
}
