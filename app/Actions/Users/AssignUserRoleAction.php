<?php

namespace App\Actions\Users;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignUserRoleAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(
        User $targetUser,
        int $churchId,
        string|MembershipRole $role,
        User $actor,
        ?string $reason = null
    ): ChurchMembership {
        return DB::transaction(function () use ($targetUser, $churchId, $role, $actor, $reason) {
            $church = Church::find($churchId);
            if (! $church) {
                throw ValidationException::withMessages([
                    'church_id' => 'The selected church does not exist.',
                ]);
            }

            $roleEnum = $role instanceof MembershipRole ? $role : MembershipRole::tryFrom((string) $role);
            if (! $roleEnum) {
                throw ValidationException::withMessages([
                    'role' => 'Invalid role specified for church membership.',
                ]);
            }

            // Check if a membership already exists for (church_id, user_id, role)
            $membership = ChurchMembership::where('church_id', $church->id)
                ->where('user_id', $targetUser->id)
                ->where('role', $roleEnum)
                ->first();

            $before = null;

            if ($membership) {
                $before = [
                    'status' => $membership->status->value,
                    'revoked_at' => $membership->revoked_at?->toIso8601String(),
                ];

                $membership->update([
                    'status' => MembershipStatus::Active,
                    'activated_at' => now(),
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'reason' => $reason ?? 'Reactivated membership role',
                ]);
            } else {
                $membership = ChurchMembership::create([
                    'church_id' => $church->id,
                    'user_id' => $targetUser->id,
                    'role' => $roleEnum,
                    'status' => MembershipStatus::Active,
                    'invited_by' => $actor->id,
                    'activated_at' => now(),
                    'reason' => $reason ?? 'Directly assigned role by administrator',
                ]);
            }

            $this->auditService->record(
                action: 'user.role_assigned',
                target: $membership,
                churchId: $church->id,
                reason: $reason ?? "Assigned {$roleEnum->value} role in {$church->name}",
                beforeData: $before,
                afterData: [
                    'membership_id' => $membership->id,
                    'user_id' => $targetUser->id,
                    'church_id' => $church->id,
                    'role' => $roleEnum->value,
                    'status' => $membership->status->value,
                ],
                actorUserId: $actor->id,
            );

            return $membership;
        });
    }
}

