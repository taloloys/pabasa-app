<?php

namespace App\Models;

use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChurchCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_id',
        'type',
        'key',
        'label',
        'description',
        'color',
        'is_active',
        'is_system',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    public function massSchedules(): HasMany
    {
        return $this->hasMany(MassSchedule::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForType(Builder $query, CategoryType|string $type): Builder
    {
        $val = $type instanceof CategoryType ? $type->value : $type;

        return $query->where('type', $val);
    }

    public function scopeForChurchOrSystem(Builder $query, ?int $churchId): Builder
    {
        return $query->where(function (Builder $q) use ($churchId) {
            $q->where('is_system', true);
            if ($churchId) {
                $q->orWhere('church_id', $churchId);
            }
        });
    }
}
