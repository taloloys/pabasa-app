<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ReadingListVersion extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Reading-list version snapshots are immutable.');
        });

        static::deleting(function () {
            throw new LogicException('Reading-list version snapshots cannot be deleted.');
        });
    }

    protected $fillable = [
        'reading_list_id',
        'version',
        'snapshot',
        'created_by',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'snapshot' => 'array',
        ];
    }

    public function readingList(): BelongsTo
    {
        return $this->belongsTo(ReadingList::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
