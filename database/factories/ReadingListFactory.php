<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\MassSchedule;
use App\Models\ReadingList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingList>
 */
class ReadingListFactory extends Factory
{
    protected $model = ReadingList::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'mass_schedule_id' => MassSchedule::factory(),
            'status' => 'preparing',
            'version' => 1,
            'finalized_at' => null,
            'finalized_by' => null,
            'reopened_at' => null,
            'reopened_by' => null,
            'reopen_reason' => null,
            'completed_at' => null,
            'completed_by' => null,
        ];
    }
}
