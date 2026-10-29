<?php

namespace App\Actions\Churches;

use App\Enums\MembershipRole;
use App\Models\Church;
use App\Models\Invitation;
use App\Models\User;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateInvitationAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * @return array{invitation: Invitation, raw_token: string}
     */
    public function execute(
        Church $church,
        string $email,
        MembershipRole|string $role,
        User $invitedBy,
        ?Carbon $expiresAt = null,
        ?string $reason = null
    ): array {
        $roleEnum = $role instanceof MembershipRole ? $role : MembershipRole::from($role);
        $rawToken = Invitation::generateRawToken();
        $tokenHash = Invitation::hashToken($rawToken);
        $expiration = $expiresAt ?? now()->addDays(7);

        return DB::transaction(function () use ($church, $email, $roleEnum, $rawToken, $tokenHash, $invitedBy, $expiration, $reason) {
            $invitation = Invitation::create([
                'church_id' => $church->id,
                'email' => strtolower(trim($email)),
                'role' => $roleEnum,
                'token_hash' => $tokenHash,
                'invited_by' => $invitedBy->id,
                'expires_at' => $expiration,
            ]);

            $this->auditService->record(
                action: 'invitation.created',
                target: $invitation,
                churchId: $church->id,
                reason: $reason,
                afterData: [
                    'church_id' => $church->id,
                    'email' => $invitation->email,
                    'role' => $invitation->role->value,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                ],
                actorUserId: $invitedBy->id,
            );

            return [
                'invitation' => $invitation,
                'raw_token' => $rawToken,
            ];
        });
    }
}
