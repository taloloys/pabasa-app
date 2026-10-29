<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'order_id',
        'church_id',
        'status',
        'requested_by_user_id',
        'request_source',
        'requested_amount',
        'approved_amount',
        'reason',
        'decision_reason',
        'requested_at',
        'decided_by',
        'decided_at',
        'transfer_reference',
        'transfer_date',
        'completed_by',
        'completed_at',
        'evidence_storage_key',
    ];

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'requested_amount' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
            'completed_at' => 'datetime',
            'transfer_date' => 'date',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
