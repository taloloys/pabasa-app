<?php

namespace App\Actions\ReadingLists;

use App\Enums\ReadingListEntryIssueStatus;
use App\Enums\ReadingListEntryStatus;
use App\Enums\ReadingListIssueResolution;
use App\Enums\ReadingListStatus;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\ReadingListEntryIssue;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ResolveReadingListEntryIssueAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(
        ReadingList $readingList,
        ReadingListEntry $entry,
        ReadingListEntryIssue $issue,
        User $resolver,
        ReadingListIssueResolution $resolution,
    ): ReadingListEntryIssue {
        return DB::transaction(function () use ($readingList, $entry, $issue, $resolver, $resolution) {
            $lockedList = ReadingList::query()
                ->whereKey($readingList->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($resolver)->authorize('resolveIssue', $lockedList);

            if (! in_array($lockedList->status, [ReadingListStatus::Preparing, ReadingListStatus::Reopened], true)) {
                throw ValidationException::withMessages([
                    'reading_list' => __('An assigned lector must reopen this list with a reason before an issue can change its contents.'),
                ]);
            }

            $lockedEntry = ReadingListEntry::query()
                ->where('reading_list_id', $lockedList->id)
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedIssue = ReadingListEntryIssue::query()
                ->where('reading_list_entry_id', $lockedEntry->id)
                ->whereKey($issue->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedIssue->church_id !== $lockedList->church_id
                || $lockedIssue->status !== ReadingListEntryIssueStatus::Open
                || $lockedEntry->status !== ReadingListEntryStatus::IssueReported
            ) {
                throw ValidationException::withMessages([
                    'issue' => __('This reading-list issue is no longer open.'),
                ]);
            }

            $lockedIssue->update([
                'status' => ReadingListEntryIssueStatus::Resolved,
                'resolution' => $resolution,
                'resolved_by' => $resolver->id,
                'resolved_at' => now(),
            ]);
            $lockedEntry->update(['status' => $resolution->value]);

            $this->audit->record(
                action: 'reading_list.entry_issue_resolved',
                target: $lockedEntry,
                churchId: $lockedList->church_id,
                reason: 'Authorized staff resolved an internal reading-list issue.',
                beforeData: ['status' => ReadingListEntryStatus::IssueReported->value],
                afterData: [
                    'issue_id' => $lockedIssue->id,
                    'issue_status' => ReadingListEntryIssueStatus::Resolved->value,
                    'resolution' => $resolution->value,
                    'entry_status' => $resolution->value,
                ],
                actorUserId: $resolver->id,
            );

            return $lockedIssue->refresh();
        });
    }
}
