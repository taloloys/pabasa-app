<?php

namespace App\Actions\ReadingLists;

use App\Enums\IntentionStatus;
use App\Enums\ReadingListStatus;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReorderReadingListAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    /**
     * @param  list<int>  $entryIds
     */
    public function execute(ReadingList $readingList, User $actor, array $entryIds): ReadingList
    {
        return DB::transaction(function () use ($readingList, $actor, $entryIds) {
            $lockedList = ReadingList::query()
                ->whereKey($readingList->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('reorder', $lockedList);

            if (! in_array($lockedList->status, [ReadingListStatus::Preparing, ReadingListStatus::Reopened], true)) {
                throw ValidationException::withMessages([
                    'reading_list' => __('Only a preparing or reopened reading list can be reordered.'),
                ]);
            }

            $submittedIds = array_map(static fn ($id) => is_int($id) ? $id : (int) $id, $entryIds);
            if (count($submittedIds) !== count(array_unique($submittedIds)) || in_array(0, $submittedIds, true)) {
                throw ValidationException::withMessages([
                    'entry_ids' => __('The requested entry order contains duplicate or invalid entries.'),
                ]);
            }

            $entries = ReadingListEntry::query()
                ->where('reading_list_id', $lockedList->id)
                ->where('status', '!=', 'excluded')
                ->with('intention')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            foreach ($entries as $entry) {
                if (
                    $entry->church_id !== $lockedList->church_id
                    || $entry->mass_schedule_id !== $lockedList->mass_schedule_id
                    || ! $entry->intention
                    || $entry->intention->church_id !== $lockedList->church_id
                    || $entry->intention->mass_schedule_id !== $lockedList->mass_schedule_id
                    || ! in_array($entry->intention->status, [
                        IntentionStatus::Confirmed,
                        IntentionStatus::CorrectionPending,
                        IntentionStatus::Completed,
                    ], true)
                ) {
                    throw ValidationException::withMessages([
                        'reading_list' => __('Every reading list entry must belong to this Mass and contain a confirmed intention.'),
                    ]);
                }
            }

            $actualIds = $entries->modelKeys();
            $expectedIds = $submittedIds;
            sort($actualIds);
            sort($expectedIds);

            if ($actualIds !== $expectedIds) {
                throw ValidationException::withMessages([
                    'entry_ids' => __('The requested entry order must include every current list entry exactly once.'),
                ]);
            }

            $beforeOrder = $entries->sortBy('position')->pluck('id')->values()->all();
            $offset = ($entries->max('position') ?? 0) + $entries->count() + 1;

            foreach ($entries->values() as $index => $entry) {
                $entry->update(['position' => $offset + $index + 1]);
            }

            foreach ($submittedIds as $index => $entryId) {
                ReadingListEntry::query()
                    ->where('reading_list_id', $lockedList->id)
                    ->whereKey($entryId)
                    ->update(['position' => $index + 1]);
            }

            $this->audit->record(
                action: 'reading_list.reordered',
                target: $lockedList,
                churchId: $lockedList->church_id,
                reason: 'Assigned lector reordered the reading list.',
                beforeData: ['entry_ids' => $beforeOrder],
                afterData: ['entry_ids' => $submittedIds],
                actorUserId: $actor->id,
            );

            return $lockedList->refresh();
        });
    }
}
