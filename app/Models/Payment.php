<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'order_id',
        'church_id',
        'expected_amount',
        'submitted_amount',
        'verified_amount',
        'currency',
        'status',
        'provider',
        'submitted_reference',
        'normalized_reference',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'resolution_code',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'expected_amount' => 'decimal:2',
            'submitted_amount' => 'decimal:2',
            'verified_amount' => 'decimal:2',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(PaymentEvidence::class);
    }

    public function latestEvidence(): HasOne
    {
        return $this->hasOne(PaymentEvidence::class)
            ->where('evidence_status', 'active')
            ->latestOfMany();
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(PaymentException::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isVerified(): bool
    {
        return $this->status === PaymentStatus::Verified;
    }
}
