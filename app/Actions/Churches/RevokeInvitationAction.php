<?php

namespace App\Actions\Churches;

use App\Models\Invitation;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RevokeInvitationAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(Invitation $invitation, User $revokedBy, ?string $reason = null): Invitation
    {
        if ($invitation->isAccepted()) {
            throw ValidationException::withMessages([
                'invitation' => ['Cannot revoke an invitation that has already been accepted.'],
            ]);
        }

        if ($invitation->isRevoked()) {
            return $invitation;
        }

        return DB::transaction(function () use ($invitation, $revokedBy, $reason) {
            $before = [
                'revoked_at' => null,
                'revoked_by' => null,
            ];

            $invitation->update([
                'revoked_at' => now(),
                'revoked_by' => $revokedBy->id,
            ]);

            $this->auditService->record(
                action: 'invitation.revoked',
                target: $invitation,
                churchId: $invitation->church_id,
                reason: $reason,
                beforeData: $before,
                afterData: [
                    'revoked_at' => $invitation->revoked_at->toIso8601String(),
                    'revoked_by' => $revokedBy->id,
                ],
                actorUserId: $revokedBy->id,
            );

            return $invitation;
        });
    }
}
