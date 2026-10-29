<?php

namespace App\Actions\Masses;

use App\Enums\BookingStatus;
use App\Enums\MassStatus;
use App\Models\MassSchedule;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CancelMassScheduleAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(MassSchedule $mass, User $actor, string $reason): MassSchedule
    {
        return DB::transaction(function () use ($mass, $actor, $reason) {
            $before = $mass->only(['status', 'booking_status', 'cancelled_at', 'cancelled_by', 'cancellation_reason']);

            $mass->update([
                'status' => MassStatus::Cancelled,
                'booking_status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancellation_reason' => $reason,
                'updated_by' => $actor->id,
            ]);

            $this->audit->record(
                action: 'mass.cancelled',
                target: $mass,
                churchId: $mass->church_id,
                reason: $reason,
                beforeData: $before,
                afterData: $mass->only(['status', 'booking_status', 'cancelled_at', 'cancelled_by', 'cancellation_reason']),
                actorUserId: $actor->id,
            );

            return $mass;
        });
    }
}
