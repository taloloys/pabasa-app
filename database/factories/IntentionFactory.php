<?php

namespace Database\Factories;

use App\Enums\IntentionStatus;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\Intention;
use App\Models\MassSchedule;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intention>
 */
class IntentionFactory extends Factory
{
    protected $model = Intention::class;

    public function definition(): array
    {
        $name = fake()->name();
        $wording = 'For the healing and spiritual recovery of '.$name;

        return [
            'order_id' => Order::factory(),
            'church_id' => Church::factory(),
            'mass_schedule_id' => MassSchedule::factory(),
            'category_id' => ChurchCategory::factory(),
            'category_label_snapshot' => 'Thanksgiving',
            'person_or_family_name' => $name,
            'wording' => $wording,
            'status' => IntentionStatus::AwaitingPayment,
            'original_name_snapshot' => $name,
            'original_wording_snapshot' => $wording,
            'sort_order' => 1,
        ];
    }
}
