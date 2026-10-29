<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'mass_schedule_id',
        'status',
        'version',
        'finalized_at',
        'finalized_by',
        'reopened_at',
        'reopened_by',
        'reopen_reason',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'finalized_at' => 'datetime',
            'reopened_at' => 'datetime',
            'completed_at' => 'datetime',
            'version' => 'integer',
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

    public function entries(): HasMany
    {
        return $this->hasMany(ReadingListEntry::class)->orderBy('position');
    }
}
