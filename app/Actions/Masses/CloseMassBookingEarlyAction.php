<?php

namespace App\Actions\Masses;

use App\Enums\BookingStatus;
use App\Models\MassSchedule;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CloseMassBookingEarlyAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(MassSchedule $mass, User $actor, ?string $reason = null): MassSchedule
    {
        return DB::transaction(function () use ($mass, $actor, $reason) {
            $before = $mass->only(['booking_status']);

            $mass->update([
                'booking_status' => BookingStatus::Closed,
                'updated_by' => $actor->id,
            ]);

            $this->audit->record(
                action: 'mass.booking_closed_early',
                target: $mass,
                churchId: $mass->church_id,
                reason: $reason ?? 'Intention bookings closed early by authorized staff/lector.',
                beforeData: $before,
                afterData: ['booking_status' => BookingStatus::Closed->value],
                actorUserId: $actor->id,
            );

            return $mass;
        });
    }
}
