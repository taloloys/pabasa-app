<?php

namespace App\Models;

use App\Enums\ReschedulingRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReschedulingRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'intention_id',
        'church_id',
        'requested_by_user_id',
        'request_source',
        'requested_mass_schedule_id',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'decision_reason',
        'payment_implication_status',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReschedulingRequestStatus::class,
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

    public function targetMassSchedule(): BelongsTo
    {
        return $this->belongsTo(MassSchedule::class, 'requested_mass_schedule_id');
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
