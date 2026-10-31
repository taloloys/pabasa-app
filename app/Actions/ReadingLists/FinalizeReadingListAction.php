<?php

namespace App\Actions\ReadingLists;

use App\Enums\IntentionStatus;
use App\Enums\MassStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReadingListEntryStatus;
use App\Enums\ReadingListStatus;
use App\Models\MassSchedule;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\ReadingListVersion;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class FinalizeReadingListAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(ReadingList $readingList, User $actor): ReadingList
    {
        return $this->finalize($readingList, $actor, false);
    }

    public function executeScheduled(ReadingList $readingList): ReadingList
    {
        return $this->finalize($readingList, null, true);
    }

    private function finalize(ReadingList $readingList, ?User $actor, bool $scheduled): ReadingList
    {
        return DB::transaction(function () use ($readingList, $actor, $scheduled) {
            $lockedList = ReadingList::query()
                ->whereKey($readingList->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($scheduled && in_array($lockedList->status, [ReadingListStatus::Finalized, ReadingListStatus::Completed], true)) {
                return $lockedList;
            }

            $mass = MassSchedule::query()
                ->whereKey($lockedList->mass_schedule_id)
                ->where('church_id', $lockedList->church_id)
                ->lockForUpdate()
                ->first();

            if (! $mass) {
                throw ValidationException::withMessages([
                    'reading_list' => __('The reading list does not match its Mass and church.'),
                ]);
            }

            if ($scheduled) {
                if (
                    $mass->status !== MassStatus::Scheduled
                    || ! $mass->finalization_deadline
                    || $mass->finalization_deadline->isFuture()
                ) {
                    throw ValidationException::withMessages([
                        'reading_list' => __('The Mass does not have a due finalization deadline.'),
                    ]);
                }

                $hasAuthorizedLector = $mass->activeAssignments()
                    ->where('assignment_role', 'lector')
                    ->with('user')
                    ->get()
                    ->contains(fn ($assignment) => $assignment->user
                        && Gate::forUser($assignment->user)->allows('finalize', $lockedList));

                if (! $hasAuthorizedLector) {
                    throw ValidationException::withMessages([
                        'reading_list' => __('An actively assigned lector is required for scheduled finalization.'),
                    ]);
                }
            } else {
                Gate::forUser($actor)->authorize('finalize', $lockedList);
            }

            if (! in_array($lockedList->status, [ReadingListStatus::Preparing, ReadingListStatus::Reopened], true)) {
                throw ValidationException::withMessages([
                    'reading_list' => __('Only a preparing or reopened reading list can be finalized.'),
                ]);
            }

            $entries = ReadingListEntry::query()
                ->where('reading_list_id', $lockedList->id)
                ->with('intention.order.payments')
                ->orderBy('position')
                ->lockForUpdate()
                ->get();

            $eligibleEntries = $entries->where('status', '!=', ReadingListEntryStatus::Excluded);

            if ($eligibleEntries->isEmpty()) {
                throw ValidationException::withMessages([
                    'reading_list' => __('A reading list must contain at least one confirmed intention before it can be finalized.'),
                ]);
            }

            foreach ($entries as $entry) {
                $intention = $entry->intention;

                if (
                    ! $intention
                    || $entry->church_id !== $lockedList->church_id
                    || $entry->mass_schedule_id !== $lockedList->mass_schedule_id
                    || $intention->church_id !== $lockedList->church_id
                    || $intention->mass_schedule_id !== $lockedList->mass_schedule_id
                    || ! $intention->order
                    || $intention->order->church_id !== $lockedList->church_id
                    || $intention->order->mass_schedule_id !== $lockedList->mass_schedule_id
                    || ! $intention->order->payments->contains(
                        fn ($payment) => $payment->status === PaymentStatus::Verified
                    )
                    || ! in_array($intention->status, [
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

            if ($eligibleEntries->contains(fn (ReadingListEntry $entry) => $entry->status === ReadingListEntryStatus::IssueReported)) {
                throw ValidationException::withMessages([
                    'reading_list' => __('Resolve all reported reading-list issues before finalizing.'),
                ]);
            }

            $previousVersion = ReadingListVersion::query()
                ->where('reading_list_id', $lockedList->id)
                ->max('version') ?? 0;
            $version = $previousVersion + 1;
            $finalizedAt = now();
            $before = [
                'status' => $lockedList->status->value,
                'version' => $lockedList->version,
            ];
            $snapshot = [
                'church_id' => $lockedList->church_id,
                'mass_schedule_id' => $lockedList->mass_schedule_id,
                'version' => $version,
                'finalized_at' => $finalizedAt->toISOString(),
                'entries' => $eligibleEntries->map(fn (ReadingListEntry $entry) => [
                    'id' => $entry->id,
                    'intention_id' => $entry->intention_id,
                    'position' => $entry->position,
                    'person_or_family_name' => $entry->intention->person_or_family_name,
                    'pronunciation_hint' => $entry->intention->pronunciation_hint,
                    'wording' => $entry->intention->wording,
                    'category_label' => $entry->intention->category_label_snapshot,
                ])->all(),
            ];

            $lockedList->update([
                'status' => ReadingListStatus::Finalized,
                'version' => $version,
                'finalized_at' => $finalizedAt,
                'finalized_by' => $actor?->id,
                'completed_at' => null,
                'completed_by' => null,
            ]);

            ReadingListVersion::create([
                'reading_list_id' => $lockedList->id,
                'version' => $version,
                'snapshot' => $snapshot,
                'created_by' => $actor?->id,
            ]);

            $this->audit->record(
                action: 'reading_list.finalized',
                target: $lockedList,
                churchId: $lockedList->church_id,
                reason: $scheduled
                    ? 'Automatically finalized after the configured Mass finalization deadline.'
                    : 'Assigned lector finalized the reading list.',
                beforeData: $before,
                afterData: [
                    'status' => ReadingListStatus::Finalized->value,
                    'version' => $version,
                    'entry_count' => $eligibleEntries->count(),
                ],
                actorUserId: $actor?->id,
                actorType: $scheduled ? 'system' : 'user',
            );

            return $lockedList->refresh();
        });
    }
}
