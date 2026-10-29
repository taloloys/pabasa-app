<?php

namespace App\Models;

use App\Enums\MassSeriesStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MassSeries extends Model
{
    use HasFactory;

    protected $table = 'mass_series';

    protected $fillable = [
        'church_id',
        'title',
        'description',
        'recurrence_rule',
        'starts_on',
        'ends_on',
        'timezone',
        'category_id',
        'location_label',
        'resource_key',
        'booking_opens_days_before',
        'booking_cutoff_hours_before',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recurrence_rule' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'booking_opens_days_before' => 'integer',
            'booking_cutoff_hours_before' => 'integer',
            'status' => MassSeriesStatus::class,
        ];
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ChurchCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MassSchedule::class, 'mass_series_id');
    }
}
