<?php

namespace App\Actions\Churches;

use App\Enums\MembershipStatus;
use App\Models\ChurchMembership;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptInvitationAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(string $rawToken, User $user): ChurchMembership
    {
        $tokenHash = Invitation::hashToken($rawToken);
        $invitation = Invitation::where('token_hash', $tokenHash)->first();

        if (! $invitation) {
            throw ValidationException::withMessages([
                'token' => ['The invitation token is invalid.'],
            ]);
        }

        if ($invitation->isRevoked()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation has been revoked.'],
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation has expired.'],
            ]);
        }

        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'token' => ['This invitation has already been accepted.'],
            ]);
        }

        return DB::transaction(function () use ($invitation, $user) {
            $invitation->update([
                'accepted_at' => now(),
            ]);

            $membership = ChurchMembership::updateOrCreate(
                [
                    'church_id' => $invitation->church_id,
                    'user_id' => $user->id,
                    'role' => $invitation->role->value,
                ],
                [
                    'status' => MembershipStatus::Active,
                    'invited_by' => $invitation->invited_by,
                    'activated_at' => now(),
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'reason' => null,
                ]
            );

            $this->auditService->record(
                action: 'invitation.accepted',
                target: $invitation,
                churchId: $invitation->church_id,
                afterData: [
                    'accepted_at' => $invitation->accepted_at->toIso8601String(),
                    'user_id' => $user->id,
                ],
                actorUserId: $user->id,
            );

            $this->auditService->record(
                action: 'membership.activated',
                target: $membership,
                churchId: $invitation->church_id,
                afterData: [
                    'church_id' => $membership->church_id,
                    'user_id' => $membership->user_id,
                    'role' => $membership->role->value,
                    'status' => $membership->status->value,
                ],
                actorUserId: $user->id,
            );

            return $membership;
        });
    }
}
