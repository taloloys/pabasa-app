<?php

namespace App\Models;

use App\Enums\ReadingListEntryIssueStatus;
use App\Enums\ReadingListIssueResolution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingListEntryIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'reading_list_entry_id',
        'church_id',
        'reported_by',
        'resolved_by',
        'status',
        'private_note',
        'resolution',
        'reported_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReadingListEntryIssueStatus::class,
            'resolution' => ReadingListIssueResolution::class,
            'reported_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(ReadingListEntry::class, 'reading_list_entry_id');
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
