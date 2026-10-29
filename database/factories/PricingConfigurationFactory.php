<?php

namespace Database\Factories;

use App\Enums\PricingStatus;
use App\Models\Church;
use App\Models\PricingConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingConfiguration>
 */
class PricingConfigurationFactory extends Factory
{
    protected $model = PricingConfiguration::class;

    public function definition(): array
    {
        return [
            'scope' => 'global',
            'church_id' => null,
            'name' => 'Standard Intention Pricing 2026',
            'currency' => 'PHP',
            'status' => PricingStatus::Approved,
            'effective_from' => now()->subMonth(),
            'effective_until' => null,
            'pricing_rules' => [
                'base_price_per_intention' => '50.00',
                'fixed_fee' => '0.00',
            ],
            'revenue_share_rules' => [
                'church_share_percentage' => '100.00',
            ],
            'approved_by' => User::factory(),
            'approved_at' => now()->subMonth(),
            'change_reason' => 'Approved initial pilot pricing',
            'version' => 1,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => PricingStatus::Draft,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    public function forChurch(Church $church): static
    {
        return $this->state(fn () => [
            'scope' => 'church',
            'church_id' => $church->id,
        ]);
    }
}
