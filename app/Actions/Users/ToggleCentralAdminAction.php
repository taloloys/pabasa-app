<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ToggleCentralAdminAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(
        User $targetUser,
        bool $isCentralAdmin,
        User $actor,
        ?string $reason = null
    ): User {
        return DB::transaction(function () use ($targetUser, $isCentralAdmin, $actor, $reason) {
            // Guard against self/sole admin lockout
            if ($targetUser->isCentralAdmin() && ! $isCentralAdmin) {
                $activeCount = User::where('is_central_admin', true)
                    ->where('status', UserStatus::Active)
                    ->count();

                if ($activeCount <= 1) {
                    throw ValidationException::withMessages([
                        'is_central_admin' => 'Cannot revoke Central Admin privileges from the only active Central Admin.',
                    ]);
                }
            }

            $before = [
                'is_central_admin' => (bool) $targetUser->is_central_admin,
            ];

            $targetUser->update([
                'is_central_admin' => $isCentralAdmin,
            ]);

            // Invalidate target user sessions if demoted to force re-authentication with new privileges
            if (! $isCentralAdmin && Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $targetUser->id)->delete();
            }

            $this->auditService->record(
                action: 'user.central_admin_toggled',
                target: $targetUser,
                reason: $reason ?? ($isCentralAdmin ? 'Granted Central Admin role' : 'Revoked Central Admin role'),
                beforeData: $before,
                afterData: [
                    'is_central_admin' => $isCentralAdmin,
                ],
                actorUserId: $actor->id,
            );

            return $targetUser;
        });
    }
}
