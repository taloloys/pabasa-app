<?php

namespace Database\Factories;

use App\Models\GuestOrderAccess;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GuestOrderAccess>
 */
class GuestOrderAccessFactory extends Factory
{
    protected $model = GuestOrderAccess::class;

    public function definition(): array
    {
        $rawToken = Str::random(60);

        return [
            'order_id' => Order::factory()->guest(),
            'token_hash' => hash('sha256', $rawToken),
            'email' => fake()->safeEmail(),
            'email_verified_at' => now(),
            'verification_code_hash' => null,
            'verification_expires_at' => null,
            'expires_at' => now()->addDays(30),
            'revoked_at' => null,
            'last_used_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
            'verification_code_hash' => hash('sha256', '123456'),
            'verification_expires_at' => now()->addMinutes(30),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'revoked_at' => now()->subHour(),
        ]);
    }
}
