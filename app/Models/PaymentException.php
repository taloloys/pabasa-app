<?php

namespace App\Models;

use App\Enums\PaymentExceptionStatus;
use App\Enums\PaymentExceptionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentException extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'church_id',
        'type',
        'status',
        'priority',
        'description',
        'resolution_code',
        'resolution_reason',
        'opened_by',
        'assigned_to',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentExceptionType::class,
            'status' => PaymentExceptionStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
