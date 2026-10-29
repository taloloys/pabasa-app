<?php

namespace App\Actions\Masses;

use App\Enums\MassAssignmentStatus;
use App\Models\MassAssignment;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class RemoveLectorAssignmentAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(MassAssignment $assignment, User $actor, ?string $reason = null): MassAssignment
    {
        return DB::transaction(function () use ($assignment, $actor, $reason) {
            $before = $assignment->only(['status', 'removed_at', 'removed_by']);

            $assignment->update([
                'status' => MassAssignmentStatus::Removed,
                'removed_at' => now(),
                'removed_by' => $actor->id,
            ]);

            $this->audit->record(
                action: 'mass.lector_removed',
                target: $assignment,
                churchId: $assignment->church_id,
                reason: $reason ?? "Lector assignment #{$assignment->id} removed.",
                beforeData: $before,
                afterData: $assignment->only(['status', 'removed_at', 'removed_by']),
                actorUserId: $actor->id,
            );

            return $assignment;
        });
    }
}
