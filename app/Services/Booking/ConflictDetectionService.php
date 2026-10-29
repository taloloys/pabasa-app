<?php

namespace App\Services\Booking;

use App\Enums\MassAssignmentStatus;
use App\Enums\MassStatus;
use App\Models\Church;
use App\Models\MassAssignment;
use App\Models\MassSchedule;
use App\Models\User;
use Carbon\Carbon;

class ConflictDetectionService
{
    /**
     * Check if a proposed or existing schedule has resource conflicts.
     * Overlapping times in different chapels/resource keys are allowed.
     *
     * @return array{has_conflict: bool, reason: ?string, conflicting_schedules: array}
     */
    public function checkResourceConflict(
        Church|int $church,
        Carbon|string $startsAt,
        Carbon|string|null $endsAt,
        ?string $resourceKey,
        ?int $excludeScheduleId = null,
    ): array {
        $churchId = $church instanceof Church ? $church->id : $church;

        // If no resource key is defined, no resource-level conflict is asserted
        if (empty($resourceKey)) {
            return [
                'has_conflict' => false,
                'reason' => null,
                'conflicting_schedules' => [],
            ];
        }

        $start = Carbon::parse($startsAt);
        // Default ends_at to 1 hour after start if not provided
        $end = $endsAt ? Carbon::parse($endsAt) : $start->copy()->addHour();

        $query = MassSchedule::query()
            ->where('church_id', $churchId)
            ->where('resource_key', $resourceKey)
            ->where('status', '!=', MassStatus::Cancelled->value)
            ->where(function ($q) use ($start, $end) {
                // Overlap condition: existing_start < new_end AND existing_end > new_start
                $q->where(function ($overlapQ) use ($start, $end) {
                    $overlapQ->where('starts_at', '<', $end)
                        ->where(function ($eq) use ($start) {
                            $eq->whereNull('ends_at')
                                ->whereRaw('DATE_ADD(starts_at, INTERVAL 1 HOUR) > ?', [$start])
                                ->orWhere('ends_at', '>', $start);
                        });
                });
            });

        if ($excludeScheduleId) {
            $query->where('id', '!=', $excludeScheduleId);
        }

        $conflicts = $query->get();

        if ($conflicts->isNotEmpty()) {
            return [
                'has_conflict' => true,
                'reason' => "Shared resource '{$resourceKey}' is already scheduled during this timeframe.",
                'conflicting_schedules' => $conflicts->toArray(),
            ];
        }

        return [
            'has_conflict' => false,
            'reason' => null,
            'conflicting_schedules' => [],
        ];
    }

    /**
     * Check if a staff member/lector is already assigned to another overlapping active Mass.
     *
     * @return array{has_conflict: bool, reason: ?string, conflicting_assignments: array}
     */
    public function checkStaffConflict(
        User|int $user,
        Carbon|string $startsAt,
        Carbon|string|null $endsAt,
        ?int $excludeScheduleId = null,
    ): array {
        $userId = $user instanceof User ? $user->id : $user;
        $start = Carbon::parse($startsAt);
        $end = $endsAt ? Carbon::parse($endsAt) : $start->copy()->addHour();

        $query = MassAssignment::query()
            ->with('massSchedule')
            ->where('user_id', $userId)
            ->where('status', MassAssignmentStatus::Active->value)
            ->whereHas('massSchedule', function ($q) use ($start, $end, $excludeScheduleId) {
                $q->where('status', '!=', MassStatus::Cancelled->value)
                    ->where('starts_at', '<', $end)
                    ->where(function ($eq) use ($start) {
                        $eq->whereNull('ends_at')
                            ->whereRaw('DATE_ADD(starts_at, INTERVAL 1 HOUR) > ?', [$start])
                            ->orWhere('ends_at', '>', $start);
                    });

                if ($excludeScheduleId) {
                    $q->where('id', '!=', $excludeScheduleId);
                }
            });

        $conflicts = $query->get();

        if ($conflicts->isNotEmpty()) {
            $userName = $user instanceof User ? $user->name : "User #{$userId}";

            return [
                'has_conflict' => true,
                'reason' => "{$userName} is already assigned to another Mass during this time window.",
                'conflicting_assignments' => $conflicts->toArray(),
            ];
        }

        return [
            'has_conflict' => false,
            'reason' => null,
            'conflicting_assignments' => [],
        ];
    }
}
