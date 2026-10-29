<?php

namespace App\Actions\Masses;

use App\Enums\BookingStatus;
use App\Enums\MassStatus;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\MassSchedule;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Booking\ConflictDetectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateMassScheduleAction
{
    public function __construct(
        protected AuditService $audit,
        protected ConflictDetectionService $conflictDetector,
    ) {}

    public function execute(Church $church, array $data, User $actor): MassSchedule
    {
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = ! empty($data['ends_at']) ? Carbon::parse($data['ends_at']) : $startsAt->copy()->addHour();

        // Booking window calculation
        $bookingOpensAt = ! empty($data['booking_opens_at'])
            ? Carbon::parse($data['booking_opens_at'])
            : $startsAt->copy()->subDays(7);

        $bookingCutoffAt = ! empty($data['booking_cutoff_at'])
            ? Carbon::parse($data['booking_cutoff_at'])
            : $startsAt->copy()->subHours(2);

        // Validation rule 7: Invalid or past booking windows are rejected
        if ($bookingOpensAt->gte($bookingCutoffAt)) {
            throw ValidationException::withMessages([
                'booking_opens_at' => ['Booking open time must be earlier than the booking cutoff time.'],
            ]);
        }

        if ($bookingCutoffAt->gt($startsAt)) {
            throw ValidationException::withMessages([
                'booking_cutoff_at' => ['Booking cutoff time cannot be after the Mass start time.'],
            ]);
        }

        // Conflict detection (spec §11 Phase 2, rule 12)
        $resourceKey = $data['resource_key'] ?? null;
        if ($resourceKey) {
            $conflict = $this->conflictDetector->checkResourceConflict(
                church: $church,
                startsAt: $startsAt,
                endsAt: $endsAt,
                resourceKey: $resourceKey,
            );

            if ($conflict['has_conflict']) {
                throw ValidationException::withMessages([
                    'resource_key' => [$conflict['reason']],
                ]);
            }
        }

        // Category snapshot
        $categoryId = $data['category_id'] ?? null;
        $categorySnapshot = null;
        if ($categoryId) {
            $category = ChurchCategory::find($categoryId);
            $categorySnapshot = $category?->label;
        }

        return DB::transaction(function () use ($church, $data, $actor, $startsAt, $endsAt, $bookingOpensAt, $bookingCutoffAt, $categorySnapshot) {
            $schedule = MassSchedule::create([
                'church_id' => $church->id,
                'mass_series_id' => $data['mass_series_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'category_label_snapshot' => $categorySnapshot,
                'title' => $data['title'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'timezone' => $data['timezone'] ?? $church->timezone ?? 'Asia/Manila',
                'language' => $data['language'] ?? 'English',
                'booking_status' => $data['booking_status'] ?? BookingStatus::Open->value,
                'status' => MassStatus::Scheduled->value,
                'booking_opens_at' => $bookingOpensAt,
                'booking_cutoff_at' => $bookingCutoffAt,
                'finalization_deadline' => ! empty($data['finalization_deadline']) ? Carbon::parse($data['finalization_deadline']) : null,
                'location_label' => $data['location_label'] ?? null,
                'resource_key' => $data['resource_key'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->audit->record(
                action: 'mass.created',
                target: $schedule,
                churchId: $church->id,
                reason: "Mass schedule '{$schedule->title}' created for {$startsAt->toDateTimeString()}.",
                afterData: $schedule->toArray(),
                actorUserId: $actor->id,
            );

            return $schedule;
        });
    }
}
