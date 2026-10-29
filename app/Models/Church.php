<?php

namespace App\Models;

use App\Enums\ChurchStatus;
use Database\Factories\ChurchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Church extends Model
{
    /** @use HasFactory<ChurchFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'contact_email',
        'contact_phone',
        'address',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'timezone',
        'verified_at',
        'verified_by',
        'published_at',
        'suspended_at',
        'suspended_by',
        'suspension_reason',
        'deactivated_at',
        'deactivated_by',
        'deactivation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => ChurchStatus::class,
            'verified_at' => 'datetime',
            'published_at' => 'datetime',
            'suspended_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ChurchMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'church_memberships')
            ->withPivot(['role', 'status', 'activated_at', 'revoked_at'])
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function branding(): HasOne
    {
        return $this->hasOne(ChurchBranding::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ChurchCategory::class);
    }

    public function massSeries(): HasMany
    {
        return $this->hasMany(MassSeries::class);
    }

    public function massSchedules(): HasMany
    {
        return $this->hasMany(MassSchedule::class);
    }

    public function massAssignments(): HasMany
    {
        return $this->hasMany(MassAssignment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function pricingConfigurations(): HasMany
    {
        return $this->hasMany(PricingConfiguration::class);
    }

    public function readingLists(): HasMany
    {
        return $this->hasMany(ReadingList::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function isPublished(): bool
    {
        return $this->status === ChurchStatus::Published;
    }

    public function isSuspended(): bool
    {
        return $this->status === ChurchStatus::Suspended;
    }

    public function isDeactivated(): bool
    {
        return $this->status === ChurchStatus::Deactivated;
    }
}
