<?php

namespace App\Models;

use App\Enums\IntentionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Intention extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'church_id',
        'mass_schedule_id',
        'category_id',
        'category_label_snapshot',
        'person_or_family_name',
        'wording',
        'status',
        'original_name_snapshot',
        'original_wording_snapshot',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'status' => IntentionStatus::class,
            'sort_order' => 'integer',
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

    public function massSchedule(): BelongsTo
    {
        return $this->belongsTo(MassSchedule::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ChurchCategory::class, 'category_id');
    }

    public function readingListEntry(): HasOne
    {
        return $this->hasOne(ReadingListEntry::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(CorrectionRequest::class);
    }
}
