<?php

namespace App\Policies;

use App\Enums\MembershipRole;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isCentralAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($payment->order && $payment->order->user_id === $user->id) {
            return true;
        }

        return $user->hasRoleInChurch($payment->church_id, MembershipRole::ChurchAdmin);
    }

    public function viewEvidence(User $user, Payment $payment): bool
    {
        // Rule 14: Lector cannot access payment evidence
        // Only Central Admin (in before), Church Admin, or the Order owner may view evidence
        if ($payment->order && $payment->order->user_id === $user->id) {
            return true;
        }

        return $user->hasRoleInChurch($payment->church_id, MembershipRole::ChurchAdmin);
    }

    public function verify(User $user, Payment $payment): bool
    {
        // Rule 13: Church Admin cannot verify/reject payment by direct POST. Central Admin only.
        return false;
    }

    public function reject(User $user, Payment $payment): bool
    {
        // Central Admin only
        return false;
    }
}
