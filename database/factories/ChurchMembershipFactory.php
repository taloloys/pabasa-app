<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Church;
use App\Models\ChurchMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChurchMembership>
 */
class ChurchMembershipFactory extends Factory
{
    protected $model = ChurchMembership::class;

    public function definition(): array
    {
        return [
            'church_id' => Church::factory(),
            'user_id' => User::factory(),
            'role' => MembershipRole::ChurchAdmin,
            'status' => MembershipStatus::Active,
            'activated_at' => now(),
        ];
    }

    public function churchAdmin(): static
    {
        return $this->state(fn () => [
            'role' => MembershipRole::ChurchAdmin,
            'status' => MembershipStatus::Active,
            'activated_at' => now(),
        ]);
    }

    public function lector(): static
    {
        return $this->state(fn () => [
            'role' => MembershipRole::Lector,
            'status' => MembershipStatus::Active,
            'activated_at' => now(),
        ]);
    }

    public function invited(): static
    {
        return $this->state(fn () => [
            'status' => MembershipStatus::Invited,
            'activated_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => MembershipStatus::Suspended,
        ]);
    }

    public function revoked(?string $reason = 'Resigned'): static
    {
        return $this->state(fn () => [
            'status' => MembershipStatus::Revoked,
            'revoked_at' => now(),
            'reason' => $reason,
        ]);
    }
}
