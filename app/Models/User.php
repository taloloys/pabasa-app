<?php

namespace App\Models;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'status', 'preferred_locale', 'is_central_admin', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'is_central_admin' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function churchMemberships(): HasMany
    {
        return $this->hasMany(ChurchMembership::class);
    }

    public function churches(): BelongsToMany
    {
        return $this->belongsToMany(Church::class, 'church_memberships')
            ->withPivot(['role', 'status', 'activated_at', 'revoked_at'])
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    public function massAssignments(): HasMany
    {
        return $this->hasMany(MassAssignment::class);
    }

    public function isCentralAdmin(): bool
    {
        return (bool) $this->is_central_admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function getActiveMembership(Church|int $church): ?ChurchMembership
    {
        $churchId = $church instanceof Church ? $church->id : $church;

        return $this->churchMemberships()
            ->where('church_id', $churchId)
            ->where('status', MembershipStatus::Active->value)
            ->first();
    }

    public function hasRoleInChurch(Church|int $church, MembershipRole|string $role): bool
    {
        $roleValue = $role instanceof MembershipRole ? $role->value : $role;
        $churchId = $church instanceof Church ? $church->id : $church;

        return $this->churchMemberships()
            ->where('church_id', $churchId)
            ->where('role', $roleValue)
            ->where('status', MembershipStatus::Active->value)
            ->exists();
    }
}
