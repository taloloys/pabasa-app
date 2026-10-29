<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\MassStatus;
use App\Models\Church;
use App\Models\MassSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MassSchedule>
 */
class MassScheduleFactory extends Factory
{
    protected $model = MassSchedule::class;

    public function definition(): array
    {
        $startsAt = now()->addDays(2)->setTime(9, 0, 0);

        return [
            'church_id' => Church::factory(),
            'mass_series_id' => null,
            'category_id' => null,
            'category_label_snapshot' => 'Sunday Mass',
            'title' => 'Sunday Holy Mass (9:00 AM)',
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addHour(),
            'timezone' => 'Asia/Manila',
            'language' => 'English',
            'booking_status' => BookingStatus::Open,
            'status' => MassStatus::Scheduled,
            'booking_opens_at' => (clone $startsAt)->subDays(7),
            'booking_cutoff_at' => (clone $startsAt)->subHours(2),
            'finalization_deadline' => (clone $startsAt)->subHour(),
            'location_label' => 'Main Sanctuary',
            'resource_key' => 'sanctuary-main',
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'booking_status' => BookingStatus::Closed,
        ]);
    }

    public function cancelled(?string $reason = 'Inclement weather'): static
    {
        return $this->state(fn () => [
            'booking_status' => BookingStatus::Cancelled,
            'status' => MassStatus::Cancelled,
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ]);
    }

    public function pastCutoff(): static
    {
        return $this->state(fn (array $attrs) => [
            'booking_opens_at' => now()->subDays(5),
            'booking_cutoff_at' => now()->subMinutes(10),
            'starts_at' => now()->addMinutes(50),
        ]);
    }
}
