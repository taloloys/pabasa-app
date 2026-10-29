<?php

namespace App\Actions\Churches;

use App\Enums\CategoryType;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageCategoryAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function create(Church $church, array $data, User $actor): ChurchCategory
    {
        return DB::transaction(function () use ($church, $data, $actor) {
            $key = Str::slug($data['key'] ?? $data['label']);

            // Check uniqueness in church scope
            $exists = ChurchCategory::where('church_id', $church->id)
                ->where('type', $data['type'] ?? CategoryType::Intention->value)
                ->where('key', $key)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'key' => ['A category with this key already exists for this church.'],
                ]);
            }

            $category = ChurchCategory::create([
                'church_id' => $church->id,
                'type' => $data['type'] ?? CategoryType::Intention->value,
                'key' => $key,
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_system' => false,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $this->audit->record(
                action: 'category.created',
                target: $category,
                churchId: $church->id,
                reason: "Category '{$category->label}' created.",
                afterData: $category->toArray(),
                actorUserId: $actor->id,
            );

            return $category;
        });
    }

    public function deactivate(ChurchCategory $category, User $actor, ?string $reason = null): ChurchCategory
    {
        return DB::transaction(function () use ($category, $actor, $reason) {
            $before = $category->only(['is_active']);

            $category->update(['is_active' => false]);

            $this->audit->record(
                action: 'category.deactivated',
                target: $category,
                churchId: $category->church_id,
                reason: $reason ?? "Category '{$category->label}' deactivated to prevent new submissions while preserving historical records.",
                beforeData: $before,
                afterData: ['is_active' => false],
                actorUserId: $actor->id,
            );

            return $category;
        });
    }

    public function activate(ChurchCategory $category, User $actor): ChurchCategory
    {
        return DB::transaction(function () use ($category, $actor) {
            $before = $category->only(['is_active']);

            $category->update(['is_active' => true]);

            $this->audit->record(
                action: 'category.activated',
                target: $category,
                churchId: $category->church_id,
                reason: "Category '{$category->label}' reactivated.",
                beforeData: $before,
                afterData: ['is_active' => true],
                actorUserId: $actor->id,
            );

            return $category;
        });
    }
}
