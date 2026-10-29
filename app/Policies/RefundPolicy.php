<?php

namespace App\Policies;

use App\Enums\MembershipRole;
use App\Models\Refund;
use App\Models\User;

class RefundPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isCentralAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Refund $refund): bool
    {
        if ($refund->order && $refund->order->user_id === $user->id) {
            return true;
        }

        return $user->hasRoleInChurch($refund->church_id, MembershipRole::ChurchAdmin);
    }

    public function decide(User $user, Refund $refund): bool
    {
        return false; // Central Admin only
    }

    public function complete(User $user, Refund $refund): bool
    {
        return false; // Central Admin only
    }
}
