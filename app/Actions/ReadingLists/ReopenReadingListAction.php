<?php

namespace App\Actions\ReadingLists;

use App\Enums\IntentionStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReadingListStatus;
use App\Models\Intention;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReopenReadingListAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(ReadingList $readingList, User $actor, string $reason): ReadingList
    {
        return DB::transaction(function () use ($readingList, $actor, $reason) {
            $lockedList = ReadingList::query()
                ->whereKey($readingList->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('reopen', $lockedList);

            if ($lockedList->status === ReadingListStatus::Completed) {
                throw ValidationException::withMessages([
                    'reading_list' => __('A completed reading list cannot be reopened through this workflow.'),
                ]);
            }

            if ($lockedList->status !== ReadingListStatus::Finalized) {
                throw ValidationException::withMessages([
                    'reading_list' => __('Only a finalized reading list can be reopened.'),
                ]);
            }

            $before = [
                'status' => $lockedList->status->value,
                'version' => $lockedList->version,
            ];
            $nextPosition = ReadingListEntry::query()
                ->where('reading_list_id', $lockedList->id)
                ->max('position') ?? 0;

            $lateIntentions = Intention::query()
                ->where('church_id', $lockedList->church_id)
                ->where('mass_schedule_id', $lockedList->mass_schedule_id)
                ->where('status', IntentionStatus::Confirmed->value)
                ->whereHas('order.payments', fn ($query) => $query->where('status', PaymentStatus::Verified->value))
                ->whereDoesntHave('readingListEntry')
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($lateIntentions as $intention) {
                $nextPosition++;

                ReadingListEntry::create([
                    'reading_list_id' => $lockedList->id,
                    'church_id' => $lockedList->church_id,
                    'mass_schedule_id' => $lockedList->mass_schedule_id,
                    'intention_id' => $intention->id,
                    'position' => $nextPosition,
                    'status' => 'pending',
                ]);
            }

            $lockedList->update([
                'status' => ReadingListStatus::Reopened,
                'reopened_at' => now(),
                'reopened_by' => $actor->id,
                'reopen_reason' => $reason,
            ]);

            $this->audit->record(
                action: 'reading_list.reopened',
                target: $lockedList,
                churchId: $lockedList->church_id,
                reason: $reason,
                beforeData: $before,
                afterData: [
                    'status' => ReadingListStatus::Reopened->value,
                    'version' => $lockedList->version,
                    'late_intentions_added' => $lateIntentions->count(),
                ],
                actorUserId: $actor->id,
            );

            return $lockedList->refresh();
        });
    }
}
