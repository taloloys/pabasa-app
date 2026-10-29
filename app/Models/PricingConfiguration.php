<?php

namespace App\Models;

use App\Enums\PricingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricingConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope',
        'church_id',
        'name',
        'currency',
        'status',
        'effective_from',
        'effective_until',
        'pricing_rules',
        'revenue_share_rules',
        'approved_by',
        'approved_at',
        'change_reason',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'status' => PricingStatus::class,
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'approved_at' => 'datetime',
            'pricing_rules' => 'array',
            'revenue_share_rules' => 'array',
            'version' => 'integer',
        ];
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isApproved(): bool
    {
        return $this->status === PricingStatus::Approved;
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', PricingStatus::Approved->value);
    }

    public function scopeForChurch(Builder $query, ?int $churchId): Builder
    {
        return $query->where(function (Builder $q) use ($churchId) {
            $q->where('scope', 'global');
            if ($churchId) {
                $q->orWhere(fn ($sub) => $sub->where('scope', 'church')->where('church_id', $churchId));
            }
        });
    }
}
