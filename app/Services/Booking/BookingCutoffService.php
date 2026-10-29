<?php

namespace App\Services\Booking;

use App\Enums\BookingStatus;
use App\Enums\ChurchStatus;
use App\Enums\MassStatus;
use App\Models\Church;
use App\Models\MassSchedule;
use Illuminate\Validation\ValidationException;

class BookingCutoffService
{
    /**
     * Determine if a Mass schedule can currently accept prayer intention bookings.
     */
    public function canAcceptBooking(MassSchedule $mass, ?Church $church = null): bool
    {
        $church = $church ?? $mass->church;

        if (! $church || $church->status !== ChurchStatus::Published) {
            return false;
        }

        if ($mass->booking_status !== BookingStatus::Open || $mass->status !== MassStatus::Scheduled) {
            return false;
        }

        $now = now();

        return $now->gte($mass->booking_opens_at) && $now->lt($mass->booking_cutoff_at);
    }

    /**
     * Validate that booking is permitted, or throw a descriptive ValidationException.
     * Can optionally perform lockForUpdate on the Mass schedule row for concurrency safety.
     *
     * @throws ValidationException
     */
    public function assertCanAcceptBooking(int|MassSchedule $massSchedule, bool $lock = false): MassSchedule
    {
        $mass = $massSchedule instanceof MassSchedule
            ? $massSchedule
            : MassSchedule::findOrFail($massSchedule);

        if ($lock) {
            $mass = MassSchedule::where('id', $mass->id)->lockForUpdate()->firstOrFail();
        }

        $church = $mass->church;

        if (! $church || $church->status !== ChurchStatus::Published) {
            throw ValidationException::withMessages([
                'church' => ['This church is currently not accepting intention submissions.'],
            ]);
        }

        if ($mass->status === MassStatus::Cancelled) {
            throw ValidationException::withMessages([
                'mass_schedule_id' => ['This Mass has been cancelled and cannot accept submissions.'],
            ]);
        }

        if ($mass->booking_status === BookingStatus::Closed || $mass->booking_status === BookingStatus::Cancelled) {
            throw ValidationException::withMessages([
                'mass_schedule_id' => ['Submissions for this Mass are currently closed.'],
            ]);
        }

        $now = now();

        if ($now->lt($mass->booking_opens_at)) {
            throw ValidationException::withMessages([
                'mass_schedule_id' => ['Bookings for this Mass have not yet opened.'],
            ]);
        }

        if ($now->gte($mass->booking_cutoff_at)) {
            throw ValidationException::withMessages([
                'mass_schedule_id' => ['The submission cutoff deadline for this Mass has passed.'],
            ]);
        }

        return $mass;
    }
}
