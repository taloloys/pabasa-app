<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'order_number',
        'user_id',
        'submitter_type',
        'church_id',
        'mass_schedule_id',
        'status',
        'currency',
        'expected_amount',
        'pricing_configuration_id',
        'pricing_snapshot',
        'submitter_name',
        'submitter_email',
        'privacy_notice_version',
        'privacy_acknowledged_at',
        'submitted_at',
        'confirmed_at',
        'cancelled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'expected_amount' => 'decimal:2',
            'pricing_snapshot' => 'array',
            'privacy_acknowledged_at' => 'datetime',
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function massSchedule(): BelongsTo
    {
        return $this->belongsTo(MassSchedule::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pricingConfiguration(): BelongsTo
    {
        return $this->belongsTo(PricingConfiguration::class);
    }

    public function intentions(): HasMany
    {
        return $this->hasMany(Intention::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function guestAccess(): HasMany
    {
        return $this->hasMany(GuestOrderAccess::class);
    }

    public function activeGuestAccess(): HasOne
    {
        return $this->hasOne(GuestOrderAccess::class)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latestOfMany();
    }

    public function accountLinks(): HasMany
    {
        return $this->hasMany(OrderAccountLink::class);
    }

    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(CancellationRequest::class);
    }

    public function reschedulingRequests(): HasMany
    {
        return $this->hasMany(ReschedulingRequest::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isVerifiedOrConfirmed(): bool
    {
        return $this->status === OrderStatus::Confirmed
            || $this->status === OrderStatus::Completed
            || $this->payments()->where('status', PaymentStatus::Verified->value)->exists();
    }

    public function canBeEditedByOwner(): bool
    {
        // Rule 25: Owner can edit eligible intentions before payment verification; edit is blocked after verification
        return ! $this->isVerifiedOrConfirmed() && $this->status !== OrderStatus::Cancelled;
    }
}
