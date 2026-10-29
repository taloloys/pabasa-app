<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Keys that must never be recorded in audit logs.
     */
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'raw_token',
        'token_hash',
        'secret',
        'credit_card',
        'cvv',
        'remember_token',
        'api_key',
        'private_key',
    ];

    /**
     * Record an audit event.
     *
     * @param  array<string, mixed>|null  $beforeData
     * @param  array<string, mixed>|null  $afterData
     */
    public function record(
        string $action,
        Model|string $target,
        ?string $targetId = null,
        ?int $churchId = null,
        ?string $reason = null,
        ?array $beforeData = null,
        ?array $afterData = null,
        ?int $actorUserId = null,
        ?string $actorType = null,
        ?string $requestId = null,
    ): AuditLog {
        $targetType = $target instanceof Model ? $target->getMorphClass() : $target;
        $resolvedTargetId = $targetId ?? ($target instanceof Model ? (string) $target->getKey() : '');

        $actorId = $actorUserId ?? Auth::id();
        $type = $actorType ?? ($actorId ? 'user' : 'system');

        $ip = Request::ip();
        $ipHash = $ip ? hash('sha256', $ip) : null;
        $userAgent = Request::userAgent();
        $userAgentSummary = $userAgent ? Str::limit($userAgent, 255) : null;

        $correlationId = $requestId ?? (string) Request::header('X-Request-Id', Str::uuid()->toString());

        return AuditLog::create([
            'actor_user_id' => $actorId,
            'actor_type' => $type,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $resolvedTargetId,
            'church_id' => $churchId,
            'request_id' => $correlationId,
            'reason' => $reason,
            'before_data' => $beforeData !== null ? $this->sanitize($beforeData) : null,
            'after_data' => $afterData !== null ? $this->sanitize($afterData) : null,
            'ip_hash' => $ipHash,
            'user_agent_summary' => $userAgentSummary,
            'created_at' => now(),
        ]);
    }

    /**
     * Recursively sanitize sensitive keys and values from data.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if ($this->isSensitiveKey($lowerKey)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);
            } elseif (is_string($value) && $this->looksLikeSensitiveContent($value)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    protected function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if (str_contains($key, $sensitive)) {
                return true;
            }
        }

        return false;
    }

    protected function looksLikeSensitiveContent(string $value): bool
    {
        // Check for password-like or token-like hashes or raw secrets if necessary
        return false;
    }
}
