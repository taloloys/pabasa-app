<?php

namespace App\Policies;

use App\Enums\MembershipRole;
use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
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
        // Church Admins can view logs, but queries will be scoped to their church
        return $user->churchMemberships()
            ->active()
            ->where('role', MembershipRole::ChurchAdmin->value)
            ->exists();
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if (! $auditLog->church_id) {
            return false;
        }

        return $user->hasRoleInChurch($auditLog->church_id, MembershipRole::ChurchAdmin);
    }
}
