<?php

namespace App\Policies;

use App\Enums\MembershipRole;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isCentralAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return true;
        }

        return $user->hasRoleInChurch($order->church_id, MembershipRole::ChurchAdmin);
    }

    public function update(User $user, Order $order): bool
    {
        if ($order->user_id === $user->id) {
            return $order->canBeEditedByOwner();
        }

        return $user->hasRoleInChurch($order->church_id, MembershipRole::ChurchAdmin);
    }

    public function editIntentions(User $user, Order $order): bool
    {
        // Rule 25: Owner can edit eligible intentions before payment verification; edit is blocked after verification
        return $order->user_id === $user->id && $order->canBeEditedByOwner();
    }

    public function requestChanges(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }
}
