<?php

namespace App\Policies;

use App\Enums\MembershipRole;
use App\Models\Church;
use App\Models\MassSchedule;
use App\Models\User;

class MassSchedulePolicy
{
    /**
     * Central Admin can perform any action.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isCentralAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * View a specific Mass schedule in staff view.
     */
    public function view(User $user, MassSchedule $mass): bool
    {
        // Church admin can view all masses for their church
        if ($user->hasRoleInChurch($mass->church_id, MembershipRole::ChurchAdmin)) {
            return true;
        }

        // Lector can ONLY view masses to which they are actively assigned (Rule 13)
        return $mass->activeAssignments()
            ->where('user_id', $user->id)
            ->where('assignment_role', 'lector')
            ->exists();
    }

    public function create(User $user, Church $church): bool
    {
        return $user->hasRoleInChurch($church->id, MembershipRole::ChurchAdmin);
    }

    public function update(User $user, MassSchedule $mass): bool
    {
        return $user->hasRoleInChurch($mass->church_id, MembershipRole::ChurchAdmin);
    }

    public function cancel(User $user, MassSchedule $mass): bool
    {
        return $user->hasRoleInChurch($mass->church_id, MembershipRole::ChurchAdmin);
    }

    public function closeEarly(User $user, MassSchedule $mass): bool
    {
        if ($user->hasRoleInChurch($mass->church_id, MembershipRole::ChurchAdmin)) {
            return true;
        }

        // Assigned lector can close submissions early (Rule 10)
        return $mass->activeAssignments()
            ->where('user_id', $user->id)
            ->where('assignment_role', 'lector')
            ->exists();
    }

    public function extendCutoff(User $user, MassSchedule $mass): bool
    {
        // Church Admin only; lectors cannot extend cutoff (Rule 11)
        return $user->hasRoleInChurch($mass->church_id, MembershipRole::ChurchAdmin);
    }

    public function assignLector(User $user, MassSchedule $mass): bool
    {
        return $user->hasRoleInChurch($mass->church_id, MembershipRole::ChurchAdmin);
    }
}
