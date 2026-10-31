<?php

namespace App\Actions\ReadingLists;

use App\Enums\IntentionStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReadingListEntryStatus;
use App\Enums\ReadingListStatus;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\ReadingListEntryIssue;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReportReadingListEntryIssueAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(ReadingList $readingList, ReadingListEntry $entry, User $reporter, string $privateNote): ReadingListEntryIssue
    {
        return DB::transaction(function () use ($readingList, $entry, $reporter, $privateNote) {
            $lockedList = ReadingList::query()
                ->whereKey($readingList->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($reporter)->authorize('reportIssue', $lockedList);

            $lockedEntry = ReadingListEntry::query()
                ->where('reading_list_id', $lockedList->id)
                ->whereKey($entry->id)
                ->with('intention.order.payments')
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedEntry->church_id !== $lockedList->church_id
                || $lockedEntry->mass_schedule_id !== $lockedList->mass_schedule_id
                || ! $lockedEntry->intention
                || $lockedEntry->intention->church_id !== $lockedList->church_id
                || $lockedEntry->intention->mass_schedule_id !== $lockedList->mass_schedule_id
                || ! $lockedEntry->intention->order
                || ! $lockedEntry->intention->order->payments->contains(
                    fn ($payment) => $payment->status === PaymentStatus::Verified
                )
                || ! in_array($lockedEntry->intention->status, [
                    IntentionStatus::Confirmed,
                    IntentionStatus::CorrectionPending,
                ], true)
            ) {
                throw ValidationException::withMessages([
                    'entry' => __('Only a paid intention belonging to this Mass can have an issue reported.'),
                ]);
            }

            if (
                $lockedList->status === ReadingListStatus::Completed
                || $lockedEntry->status !== ReadingListEntryStatus::Pending
            ) {
                throw ValidationException::withMessages([
                    'entry' => __('Only a pending entry on an unfinished reading list can have an issue reported.'),
                ]);
            }

            $issue = ReadingListEntryIssue::create([
                'reading_list_entry_id' => $lockedEntry->id,
                'church_id' => $lockedList->church_id,
                'reported_by' => $reporter->id,
                'status' => 'open',
                'private_note' => $privateNote,
                'reported_at' => now(),
            ]);

            $lockedEntry->update(['status' => ReadingListEntryStatus::IssueReported]);

            $this->audit->record(
                action: 'reading_list.entry_issue_reported',
                target: $lockedEntry,
                churchId: $lockedList->church_id,
                reason: 'Assigned lector reported an internal reading-list issue.',
                afterData: [
                    'issue_id' => $issue->id,
                    'status' => ReadingListEntryStatus::IssueReported->value,
                ],
                actorUserId: $reporter->id,
            );

            return $issue;
        });
    }
}
