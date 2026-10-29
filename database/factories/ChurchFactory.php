<?php

namespace Database\Factories;

use App\Enums\ChurchStatus;
use App\Models\Church;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Church>
 */
class ChurchFactory extends Factory
{
    protected $model = Church::class;

    public function definition(): array
    {
        $name = fake()->company().' Parish';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 9999),
            'status' => ChurchStatus::Published,
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '+63917'.fake()->numerify('#######'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => 'Cebu',
            'postal_code' => '6000',
            'latitude' => 10.3156992,
            'longitude' => 123.8854366,
            'timezone' => 'Asia/Manila',
            'verified_at' => now(),
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => ChurchStatus::Draft,
            'verified_at' => null,
            'published_at' => null,
        ]);
    }

    public function suspended(?string $reason = 'Administrative review'): static
    {
        return $this->state(fn () => [
            'status' => ChurchStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    public function deactivated(?string $reason = 'Closure'): static
    {
        return $this->state(fn () => [
            'status' => ChurchStatus::Deactivated,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason,
        ]);
    }
}
