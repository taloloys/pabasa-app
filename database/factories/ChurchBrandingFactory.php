<?php

namespace Database\Factories;

use App\Models\Church;
use App\Models\ChurchBranding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChurchBranding>
 */
class ChurchBrandingFactory extends Factory
{
    protected $model = ChurchBranding::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'logo_path' => 'branding/logos/default.png',
            'banner_path' => 'branding/banners/default.jpg',
            'theme_color' => '#7e46c4',
            'public_description' => fake()->paragraph(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '+63917'.fake()->numerify('#######'),
            'approved_at' => now(),
            'approved_by' => null,
        ];
    }
}
