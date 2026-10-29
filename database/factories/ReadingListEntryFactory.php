<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\Intention;
use App\Models\MassSchedule;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingListEntry>
 */
class ReadingListEntryFactory extends Factory
{
    protected $model = ReadingListEntry::class;

    public function definition(): array
    {
        return [
            'reading_list_id' => ReadingList::factory(),
            'church_id' => Church::factory(),
            'mass_schedule_id' => MassSchedule::factory(),
            'intention_id' => Intention::factory(),
            'position' => 1,
            'status' => 'pending',
            'completed_at' => null,
            'completed_by' => null,
        ];
    }
}
