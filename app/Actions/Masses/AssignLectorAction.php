<?php

namespace App\Actions\Masses;

use App\Enums\MassAssignmentStatus;
use App\Enums\MembershipRole;
use App\Models\MassAssignment;
use App\Models\MassSchedule;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Booking\ConflictDetectionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignLectorAction
{
    public function __construct(
        protected AuditService $audit,
        protected ConflictDetectionService $conflictDetector,
    ) {}

    public function execute(MassSchedule $mass, User $lector, User $actor, ?string $notes = null): MassAssignment
    {
        // 1. Verify user belongs to this church as lector or admin
        $isLector = $lector->hasRoleInChurch($mass->church_id, MembershipRole::Lector)
            || $lector->hasRoleInChurch($mass->church_id, MembershipRole::ChurchAdmin);

        if (! $isLector) {
            throw ValidationException::withMessages([
                'user_id' => ['The selected user is not an active lector at this church.'],
            ]);
        }

        // 2. Check for staff schedule conflict
        $conflict = $this->conflictDetector->checkStaffConflict(
            user: $lector,
            startsAt: $mass->starts_at,
            endsAt: $mass->ends_at,
            excludeScheduleId: $mass->id,
        );

        if ($conflict['has_conflict']) {
            throw ValidationException::withMessages([
                'user_id' => [$conflict['reason']],
            ]);
        }

        // 3. Prevent duplicate active assignment
        $alreadyAssigned = MassAssignment::where('mass_schedule_id', $mass->id)
            ->where('user_id', $lector->id)
            ->where('assignment_role', 'lector')
            ->where('status', MassAssignmentStatus::Active->value)
            ->exists();

        if ($alreadyAssigned) {
            throw ValidationException::withMessages([
                'user_id' => ['This lector is already actively assigned to this Mass.'],
            ]);
        }

        return DB::transaction(function () use ($mass, $lector, $actor, $notes) {
            $assignment = MassAssignment::create([
                'church_id' => $mass->church_id,
                'mass_schedule_id' => $mass->id,
                'user_id' => $lector->id,
                'assignment_role' => 'lector',
                'status' => MassAssignmentStatus::Active,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'notes' => $notes,
            ]);

            $this->audit->record(
                action: 'mass.lector_assigned',
                target: $assignment,
                churchId: $mass->church_id,
                reason: "Lector {$lector->name} assigned to Mass #{$mass->id} ({$mass->title}).",
                afterData: $assignment->toArray(),
                actorUserId: $actor->id,
            );

            return $assignment;
        });
    }
}
