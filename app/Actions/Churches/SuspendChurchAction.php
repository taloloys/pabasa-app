<?php

namespace App\Actions\Churches;

use App\Enums\ChurchStatus;
use App\Models\Church;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class SuspendChurchAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(Church $church, User $actor, string $reason): Church
    {
        return DB::transaction(function () use ($church, $actor, $reason) {
            $before = $church->only(['status', 'suspended_at', 'suspended_by', 'suspension_reason']);

            $church->update([
                'status' => ChurchStatus::Suspended,
                'suspended_at' => now(),
                'suspended_by' => $actor->id,
                'suspension_reason' => $reason,
            ]);

            $this->audit->record(
                action: 'church.suspended',
                target: $church,
                churchId: $church->id,
                reason: $reason,
                beforeData: $before,
                afterData: $church->only(['status', 'suspended_at', 'suspended_by', 'suspension_reason']),
                actorUserId: $actor->id,
            );

            return $church;
        });
    }
}
