<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Church;
use App\Models\ChurchCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChurchCategory>
 */
class ChurchCategoryFactory extends Factory
{
    protected $model = ChurchCategory::class;

    public function definition(): array
    {
        $label = fake()->words(2, true);

        return [
            'church_id' => Church::factory(),
            'type' => CategoryType::Intention,
            'key' => fake()->unique()->slug(2),
            'label' => ucwords($label),
            'description' => fake()->sentence(),
            'color' => '#7e46c4',
            'is_active' => true,
            'is_system' => false,
            'sort_order' => 0,
        ];
    }

    public function mass(): static
    {
        return $this->state(fn () => [
            'type' => CategoryType::Mass,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn () => [
            'church_id' => null,
            'is_system' => true,
        ]);
    }
}
