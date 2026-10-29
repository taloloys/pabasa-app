<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Church;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\PricingConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'submitter_type' => 'registered_user',
            'church_id' => Church::factory(),
            'mass_schedule_id' => MassSchedule::factory(),
            'status' => OrderStatus::AwaitingPayment,
            'currency' => 'PHP',
            'expected_amount' => '100.00',
            'pricing_configuration_id' => PricingConfiguration::factory(),
            'pricing_snapshot' => [
                'price_per_intention' => '50.00',
                'intention_count' => 2,
                'total_amount' => '100.00',
            ],
            'submitter_name' => fake()->name(),
            'submitter_email' => fake()->safeEmail(),
            'privacy_notice_version' => '1.0',
            'privacy_acknowledged_at' => now(),
            'submitted_at' => now(),
            'confirmed_at' => null,
            'cancelled_at' => null,
            'completed_at' => null,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => [
            'user_id' => null,
            'submitter_type' => 'guest',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::PaymentUnderReview,
        ]);
    }
}
