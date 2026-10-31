<?php

namespace App\Policies;

use App\Enums\MembershipRole;
use App\Models\ReadingList;
use App\Models\User;

class ReadingListPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isCentralAdmin() && ! in_array($ability, [
            'correctEntryCompletion',
            'reportIssue',
            'resolveIssue',
            'updatePronunciationHint',
        ], true)) {
            return true;
        }

        return null;
    }

    public function view(User $user, ReadingList $readingList): bool
    {
        return $user->hasRoleInChurch($readingList->church_id, MembershipRole::ChurchAdmin)
            || $this->isAssignedLector($user, $readingList);
    }

    public function reorder(User $user, ReadingList $readingList): bool
    {
        return $this->isAssignedLector($user, $readingList);
    }

    public function finalize(User $user, ReadingList $readingList): bool
    {
        return $this->isAssignedLector($user, $readingList);
    }

    public function reopen(User $user, ReadingList $readingList): bool
    {
        return $this->isAssignedLector($user, $readingList);
    }

    public function completeEntry(User $user, ReadingList $readingList): bool
    {
        return $this->isAssignedLector($user, $readingList);
    }

    public function correctEntryCompletion(User $user, ReadingList $readingList): bool
    {
        return $this->isAssignedLector($user, $readingList);
    }

    public function reportIssue(User $user, ReadingList $readingList): bool
    {
        return $this->isAssignedLector($user, $readingList);
    }

    public function resolveIssue(User $user, ReadingList $readingList): bool
    {
        return $user->hasRoleInChurch($readingList->church_id, MembershipRole::ChurchAdmin)
            || $this->isAssignedLector($user, $readingList);
    }

    public function updatePronunciationHint(User $user, ReadingList $readingList): bool
    {
        return $user->hasRoleInChurch($readingList->church_id, MembershipRole::ChurchAdmin);
    }

    private function isAssignedLector(User $user, ReadingList $readingList): bool
    {
        return $user->hasRoleInChurch($readingList->church_id, MembershipRole::Lector)
            && $readingList->massSchedule()
                ->whereHas('activeAssignments', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->where('assignment_role', 'lector');
                })
                ->exists();
    }
}
