<?php

namespace App\Models;

use App\Enums\MassAssignmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MassAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'mass_schedule_id',
        'user_id',
        'assignment_role',
        'status',
        'assigned_by',
        'assigned_at',
        'removed_at',
        'removed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => MassAssignmentStatus::class,
            'assigned_at' => 'datetime',
            'removed_at' => 'datetime',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MassAssignmentStatus::Active->value);
    }
}
