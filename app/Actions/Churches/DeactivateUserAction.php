<?php

namespace App\Actions\Churches;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeactivateUserAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(User $user, User $deactivatedBy, ?string $reason = null): User
    {
        return DB::transaction(function () use ($user, $deactivatedBy, $reason) {
            $before = [
                'status' => $user->status->value,
            ];

            $user->update([
                'status' => UserStatus::Disabled,
            ]);

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $this->auditService->record(
                action: 'user.deactivated',
                target: $user,
                reason: $reason,
                beforeData: $before,
                afterData: [
                    'status' => $user->status->value,
                ],
                actorUserId: $deactivatedBy->id,
            );

            return $user;
        });
    }
}
