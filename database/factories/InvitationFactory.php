<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Models\Church;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    protected $model = Invitation::class;

    public function definition(): array
    {
        $rawToken = Invitation::generateRawToken();

        return [
            'church_id' => Church::factory(),
            'email' => fake()->safeEmail(),
            'role' => MembershipRole::Lector,
            'token_hash' => Invitation::hashToken($rawToken),
            'invited_by' => User::factory(),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'revoked_at' => null,
        ];
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
            'revoked_at' => now(),
            'revoked_by' => User::factory(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn () => [
            'accepted_at' => now(),
        ]);
    }
}
