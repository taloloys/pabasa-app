<?php

namespace App\Actions\Churches;

use App\Enums\MembershipStatus;
use App\Models\ChurchMembership;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RevokeMembershipAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(ChurchMembership $membership, User $revokedBy, ?string $reason = null): ChurchMembership
    {
        return DB::transaction(function () use ($membership, $revokedBy, $reason) {
            $before = [
                'status' => $membership->status->value,
                'revoked_at' => $membership->revoked_at?->toIso8601String(),
                'revoked_by' => $membership->revoked_by,
                'reason' => $membership->reason,
            ];

            $membership->update([
                'status' => MembershipStatus::Revoked,
                'revoked_at' => now(),
                'revoked_by' => $revokedBy->id,
                'reason' => $reason,
            ]);

            // Invalidate user sessions if sessions table exists
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $membership->user_id)->delete();
            }

            $this->auditService->record(
                action: 'membership.revoked',
                target: $membership,
                churchId: $membership->church_id,
                reason: $reason,
                beforeData: $before,
                afterData: [
                    'status' => $membership->status->value,
                    'revoked_at' => $membership->revoked_at->toIso8601String(),
                    'revoked_by' => $revokedBy->id,
                    'reason' => $reason,
                ],
                actorUserId: $revokedBy->id,
            );

            return $membership;
        });
    }
}
