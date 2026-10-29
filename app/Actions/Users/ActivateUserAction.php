<?php

namespace App\Actions\Users;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class ActivateUserAction
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    public function execute(User $user, User $activatedBy, ?string $reason = null): User
    {
        return DB::transaction(function () use ($user, $activatedBy, $reason) {
            $before = [
                'status' => $user->status->value,
            ];

            $user->update([
                'status' => UserStatus::Active,
            ]);

            $this->auditService->record(
                action: 'user.activated',
                target: $user,
                reason: $reason ?? 'User account activated by administrator',
                beforeData: $before,
                afterData: [
                    'status' => $user->status->value,
                ],
                actorUserId: $activatedBy->id,
            );

            return $user;
        });
    }
}

