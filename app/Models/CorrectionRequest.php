<?php

namespace App\Models;

use App\Enums\CorrectionRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'intention_id',
        'church_id',
        'requested_by_user_id',
        'request_source',
        'old_values',
        'requested_values',
        'status',
        'reviewed_by',
        'reviewed_at',
        'decision_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => CorrectionRequestStatus::class,
            'old_values' => 'array',
            'requested_values' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function intention(): BelongsTo
    {
        return $this->belongsTo(Intention::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
