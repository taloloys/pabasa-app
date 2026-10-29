<?php

namespace App\Models;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use Database\Factories\ChurchMembershipFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchMembership extends Model
{
    /** @use HasFactory<ChurchMembershipFactory> */
    use HasFactory;

    protected $fillable = [
        'church_id',
        'user_id',
        'role',
        'status',
        'invited_by',
        'activated_at',
        'revoked_at',
        'revoked_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'role' => MembershipRole::class,
            'status' => MembershipStatus::class,
            'activated_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MembershipStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === MembershipStatus::Active;
    }

    public function isChurchAdmin(): bool
    {
        return $this->role === MembershipRole::ChurchAdmin;
    }

    public function isLector(): bool
    {
        return $this->role === MembershipRole::Lector;
    }
}
