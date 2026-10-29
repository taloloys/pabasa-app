<?php

namespace App\Actions\Masses;

use App\Enums\MembershipRole;
use App\Models\MassSchedule;
use App\Models\User;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExtendMassCutoffAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(MassSchedule $mass, Carbon|string $newCutoffAt, User $actor, ?string $reason = null): MassSchedule
    {
        // Only ChurchAdmin or CentralAdmin can extend cutoff; lectors are explicitly forbidden
        if (! $actor->isCentralAdmin() && ! $actor->hasRoleInChurch($mass->church_id, MembershipRole::ChurchAdmin)) {
            throw new AuthorizationException('Only church administrators are authorized to extend the submission cutoff deadline.');
        }

        $cutoff = Carbon::parse($newCutoffAt);

        // Cannot extend cutoff beyond the Mass start time
        if ($cutoff->gt($mass->starts_at)) {
            throw ValidationException::withMessages([
                'booking_cutoff_at' => ['Booking cutoff deadline cannot be extended beyond the Mass start time.'],
            ]);
        }

        // New cutoff must be greater than current cutoff
        if ($cutoff->lte($mass->booking_cutoff_at)) {
            throw ValidationException::withMessages([
                'booking_cutoff_at' => ['New cutoff time must be later than the existing cutoff time.'],
            ]);
        }

        return DB::transaction(function () use ($mass, $cutoff, $actor, $reason) {
            $before = $mass->only(['booking_cutoff_at']);

            $mass->update([
                'booking_cutoff_at' => $cutoff,
                'updated_by' => $actor->id,
            ]);

            $this->audit->record(
                action: 'mass.cutoff_extended',
                target: $mass,
                churchId: $mass->church_id,
                reason: $reason ?? "Cutoff deadline extended to {$cutoff->toDateTimeString()}.",
                beforeData: $before,
                afterData: ['booking_cutoff_at' => $cutoff->toDateTimeString()],
                actorUserId: $actor->id,
            );

            return $mass;
        });
    }
}
