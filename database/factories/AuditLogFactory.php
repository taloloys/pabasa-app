<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory(),
            'actor_type' => 'user',
            'action' => 'membership.revoked',
            'target_type' => 'church_membership',
            'target_id' => (string) fake()->numberBetween(1, 100),
            'church_id' => null,
            'request_id' => Str::uuid()->toString(),
            'reason' => 'Audit test reason',
            'before_data' => ['status' => 'active'],
            'after_data' => ['status' => 'revoked'],
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent_summary' => 'PHPUnit Test Agent',
            'created_at' => now(),
        ];
    }
}
