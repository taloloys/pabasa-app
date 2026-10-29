<?php

namespace App\Actions\Churches;

use App\Enums\ChurchStatus;
use App\Models\Church;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class PublishChurchAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(Church $church, User $actor, ?string $reason = null): Church
    {
        return DB::transaction(function () use ($church, $actor, $reason) {
            $before = $church->only(['status', 'published_at']);

            $church->update([
                'status' => ChurchStatus::Published,
                'published_at' => now(),
            ]);

            $this->audit->record(
                action: 'church.published',
                target: $church,
                churchId: $church->id,
                reason: $reason ?? 'Church published for public directory and intention bookings.',
                beforeData: $before,
                afterData: $church->only(['status', 'published_at']),
                actorUserId: $actor->id,
            );

            return $church;
        });
    }
}
