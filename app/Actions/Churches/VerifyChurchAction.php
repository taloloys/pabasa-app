<?php

namespace App\Actions\Churches;

use App\Enums\ChurchStatus;
use App\Models\Church;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class VerifyChurchAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(Church $church, User $actor, ?string $reason = null): Church
    {
        return DB::transaction(function () use ($church, $actor, $reason) {
            $before = $church->only(['status', 'verified_at', 'verified_by']);

            $church->update([
                'status' => ChurchStatus::Verified,
                'verified_at' => now(),
                'verified_by' => $actor->id,
            ]);

            $this->audit->record(
                action: 'church.verified',
                target: $church,
                churchId: $church->id,
                reason: $reason ?? 'Church identity and documentation verified by Central Admin.',
                beforeData: $before,
                afterData: $church->only(['status', 'verified_at', 'verified_by']),
                actorUserId: $actor->id,
            );

            return $church;
        });
    }
}
