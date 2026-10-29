<?php

namespace Database\Factories;

use App\Enums\MassAssignmentStatus;
use App\Models\Church;
use App\Models\MassAssignment;
use App\Models\MassSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MassAssignment>
 */
class MassAssignmentFactory extends Factory
{
    protected $model = MassAssignment::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'mass_schedule_id' => MassSchedule::factory(),
            'user_id' => User::factory(),
            'assignment_role' => 'lector',
            'status' => MassAssignmentStatus::Active,
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'removed_at' => null,
            'removed_by' => null,
            'notes' => null,
        ];
    }

    public function removed(): static
    {
        return $this->state(fn () => [
            'status' => MassAssignmentStatus::Removed,
            'removed_at' => now(),
        ]);
    }
}
