<?php

namespace Database\Factories;

use App\Enums\MassSeriesStatus;
use App\Models\Church;
use App\Models\MassSeries;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MassSeries>
 */
class MassSeriesFactory extends Factory
{
    protected $model = MassSeries::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'title' => 'Sunday Morning Mass Series',
            'description' => 'Regular Sunday weekly Holy Mass',
            'recurrence_rule' => [
                'days_of_week' => [0],
                'time' => '08:00',
                'duration_minutes' => 60,
            ],
            'starts_on' => now()->startOfWeek()->toDateString(),
            'ends_on' => now()->addWeeks(8)->toDateString(),
            'timezone' => 'Asia/Manila',
            'category_id' => null,
            'location_label' => 'Main Sanctuary',
            'resource_key' => 'sanctuary-main',
            'booking_opens_days_before' => 7,
            'booking_cutoff_hours_before' => 2,
            'status' => MassSeriesStatus::Active,
            'created_by' => User::factory(),
        ];
    }
}
