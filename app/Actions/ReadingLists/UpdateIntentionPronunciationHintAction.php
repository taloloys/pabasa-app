<?php

namespace App\Actions\ReadingLists;

use App\Enums\IntentionStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReadingListStatus;
use App\Models\Intention;
use App\Models\ReadingList;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateIntentionPronunciationHintAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(ReadingList $readingList, Intention $intention, User $churchAdmin, ?string $hint): Intention
    {
        return DB::transaction(function () use ($readingList, $intention, $churchAdmin, $hint) {
            $lockedList = ReadingList::query()
                ->whereKey($readingList->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($churchAdmin)->authorize('updatePronunciationHint', $lockedList);

            $lockedIntention = Intention::query()
                ->whereKey($intention->id)
                ->whereHas('order.payments', fn ($query) => $query->where('status', PaymentStatus::Verified->value))
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedIntention->church_id !== $lockedList->church_id
                || $lockedIntention->mass_schedule_id !== $lockedList->mass_schedule_id
                || $lockedList->entries()->where('intention_id', $lockedIntention->id)->doesntExist()
                || ! in_array($lockedIntention->status, [
                    IntentionStatus::Confirmed,
                    IntentionStatus::CorrectionPending,
                ], true)
            ) {
                throw ValidationException::withMessages([
                    'intention' => __('A pronunciation hint can only be added to a paid intention on this reading list.'),
                ]);
            }

            if (! in_array($lockedList->status, [ReadingListStatus::Preparing, ReadingListStatus::Reopened], true)) {
                throw ValidationException::withMessages([
                    'reading_list' => __('An assigned lector must reopen this list with a reason before pronunciation hints can change.'),
                ]);
            }

            $before = ['pronunciation_hint' => $lockedIntention->pronunciation_hint];
            $lockedIntention->update(['pronunciation_hint' => $hint]);

            $this->audit->record(
                action: 'reading_list.pronunciation_hint_updated',
                target: $lockedIntention,
                churchId: $lockedList->church_id,
                reason: 'Church Admin updated separate pronunciation guidance.',
                beforeData: $before,
                afterData: ['pronunciation_hint' => $hint],
                actorUserId: $churchAdmin->id,
            );

            return $lockedIntention->refresh();
        });
    }
}
