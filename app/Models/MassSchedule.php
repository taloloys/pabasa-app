<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\MassAssignmentStatus;
use App\Enums\MassStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'mass_series_id',
        'category_id',
        'category_label_snapshot',
        'title',
        'starts_at',
        'ends_at',
        'timezone',
        'language',
        'booking_status',
        'status',
        'booking_opens_at',
        'booking_cutoff_at',
        'finalization_deadline',
        'location_label',
        'resource_key',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'booking_opens_at' => 'datetime',
            'booking_cutoff_at' => 'datetime',
            'finalization_deadline' => 'datetime',
            'cancelled_at' => 'datetime',
            'booking_status' => BookingStatus::class,
            'status' => MassStatus::class,
        ];
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function massSeries(): BelongsTo
    {
        return $this->belongsTo(MassSeries::class, 'mass_series_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ChurchCategory::class, 'category_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MassAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->hasMany(MassAssignment::class)->where('status', MassAssignmentStatus::Active->value);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function intentions(): HasMany
    {
        return $this->hasMany(Intention::class);
    }

    public function readingList(): HasOne
    {
        return $this->hasOne(ReadingList::class);
    }

    public function lectors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mass_assignments')
            ->wherePivot('status', MassAssignmentStatus::Active->value)
            ->wherePivot('assignment_role', 'lector')
            ->withPivot(['id', 'status', 'assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isBookingOpen(): bool
    {
        if ($this->booking_status !== BookingStatus::Open || $this->status !== MassStatus::Scheduled) {
            return false;
        }

        // Church must be published
        if (! $this->church || ! $this->church->isPublished()) {
            return false;
        }

        $now = now();

        return $now->gte($this->booking_opens_at) && $now->lt($this->booking_cutoff_at);
    }

    public function isCutoffPassed(): bool
    {
        return now()->gte($this->booking_cutoff_at);
    }

    public function formatInChurchTimezone(string $attribute = 'starts_at', string $format = 'g:i A'): string
    {
        /** @var Carbon|null $value */
        $value = $this->{$attribute};
        if (! $value) {
            return '';
        }

        $tz = $this->timezone ?: ($this->church?->timezone ?: 'Asia/Manila');

        return $value->copy()->setTimezone($tz)->format($format);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>=', now())
            ->where('status', '!=', MassStatus::Cancelled->value)
            ->orderBy('starts_at', 'asc');
    }

    public function scopeOpenForBooking(Builder $query): Builder
    {
        $now = now();

        return $query->where('booking_status', BookingStatus::Open->value)
            ->where('status', MassStatus::Scheduled->value)
            ->where('booking_opens_at', '<=', $now)
            ->where('booking_cutoff_at', '>', $now);
    }
}
