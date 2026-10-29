<?php

namespace App\Actions\Masses;

use App\Models\ChurchCategory;
use App\Models\MassSchedule;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Booking\ConflictDetectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateMassOccurrenceAction
{
    public function __construct(
        protected AuditService $audit,
        protected ConflictDetectionService $conflictDetector,
    ) {}

    public function execute(MassSchedule $mass, array $data, User $actor): MassSchedule
    {
        $startsAt = isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : $mass->starts_at;
        $endsAt = isset($data['ends_at']) ? Carbon::parse($data['ends_at']) : $mass->ends_at;

        $bookingOpensAt = isset($data['booking_opens_at']) ? Carbon::parse($data['booking_opens_at']) : $mass->booking_opens_at;
        $bookingCutoffAt = isset($data['booking_cutoff_at']) ? Carbon::parse($data['booking_cutoff_at']) : $mass->booking_cutoff_at;

        if ($bookingOpensAt && $bookingCutoffAt && $bookingOpensAt->gte($bookingCutoffAt)) {
            throw ValidationException::withMessages([
                'booking_opens_at' => ['Booking open time must be earlier than the booking cutoff time.'],
            ]);
        }

        if ($bookingCutoffAt && $startsAt && $bookingCutoffAt->gt($startsAt)) {
            throw ValidationException::withMessages([
                'booking_cutoff_at' => ['Booking cutoff time cannot be after the Mass start time.'],
            ]);
        }

        $resourceKey = array_key_exists('resource_key', $data) ? $data['resource_key'] : $mass->resource_key;
        if ($resourceKey) {
            $conflict = $this->conflictDetector->checkResourceConflict(
                church: $mass->church_id,
                startsAt: $startsAt,
                endsAt: $endsAt,
                resourceKey: $resourceKey,
                excludeScheduleId: $mass->id,
            );

            if ($conflict['has_conflict']) {
                throw ValidationException::withMessages([
                    'resource_key' => [$conflict['reason']],
                ]);
            }
        }

        return DB::transaction(function () use ($mass, $data, $actor, $startsAt, $endsAt, $bookingOpensAt, $bookingCutoffAt, $resourceKey) {
            $before = $mass->toArray();

            $updateData = array_filter([
                'title' => $data['title'] ?? $mass->title,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'timezone' => $data['timezone'] ?? $mass->timezone,
                'language' => $data['language'] ?? $mass->language,
                'location_label' => array_key_exists('location_label', $data) ? $data['location_label'] : $mass->location_label,
                'resource_key' => $resourceKey,
                'booking_opens_at' => $bookingOpensAt,
                'booking_cutoff_at' => $bookingCutoffAt,
                'updated_by' => $actor->id,
            ], fn ($v) => $v !== null);

            if (isset($data['category_id'])) {
                $updateData['category_id'] = $data['category_id'];
                $cat = ChurchCategory::find($data['category_id']);
                $updateData['category_label_snapshot'] = $cat?->label;
            }

            $mass->update($updateData);

            $this->audit->record(
                action: 'mass.updated',
                target: $mass,
                churchId: $mass->church_id,
                reason: "Mass occurrence #{$mass->id} updated. Sibling occurrences remain unaffected.",
                beforeData: $before,
                afterData: $mass->fresh()->toArray(),
                actorUserId: $actor->id,
            );

            return $mass;
        });
    }
}
