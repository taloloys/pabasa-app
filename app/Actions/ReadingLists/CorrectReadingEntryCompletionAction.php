<?php

namespace App\Actions\ReadingLists;

use App\Enums\IntentionStatus;
use App\Enums\OrderStatus;
use App\Enums\ReadingListEntryStatus;
use App\Enums\ReadingListStatus;
use App\Models\Intention;
use App\Models\Order;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CorrectReadingEntryCompletionAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(ReadingList $readingList, ReadingListEntry $entry, User $actor, string $reason): ReadingListEntry
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => __('A reason is required to correct a completed reading entry.'),
            ]);
        }

        return DB::transaction(function () use ($readingList, $entry, $actor, $reason) {
            if ($entry->reading_list_id !== $readingList->id) {
                abort(404);
            }

            $intentionId = ReadingListEntry::query()
                ->where('reading_list_id', $readingList->id)
                ->whereKey($entry->id)
                ->value('intention_id');
            $orderId = Intention::query()->whereKey($intentionId)->value('order_id');
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();

            $lockedList = ReadingList::query()
                ->whereKey($readingList->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('correctEntryCompletion', $lockedList);

            $lockedEntry = ReadingListEntry::query()
                ->where('reading_list_id', $lockedList->id)
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedList->status !== ReadingListStatus::Finalized) {
                throw ValidationException::withMessages([
                    'reading_list' => __('A completed reading entry can only be corrected while its list remains unfinished.'),
                ]);
            }

            if ($lockedEntry->status !== ReadingListEntryStatus::Completed) {
                throw ValidationException::withMessages([
                    'entry' => __('Only a completed reading entry can have its completion corrected.'),
                ]);
            }

            $intention = Intention::query()
                ->whereKey($lockedEntry->intention_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $intention->church_id !== $lockedList->church_id
                || $intention->mass_schedule_id !== $lockedList->mass_schedule_id
                || $intention->status !== IntentionStatus::Completed
                || $order->church_id !== $lockedList->church_id
                || $order->mass_schedule_id !== $lockedList->mass_schedule_id
                || ! in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Completed], true)
            ) {
                throw ValidationException::withMessages([
                    'entry' => __('The completed entry, intention, and order must all belong to this Mass and be in a correctable state.'),
                ]);
            }

            $before = [
                'entry_status' => $lockedEntry->status->value,
                'completed_at' => $lockedEntry->completed_at?->toISOString(),
                'completed_by' => $lockedEntry->completed_by,
                'intention_status' => $intention->status->value,
                'order_status' => $order->status->value,
            ];

            $intention->update(['status' => IntentionStatus::Confirmed]);
            $lockedEntry->update([
                'status' => ReadingListEntryStatus::Pending,
                'completed_at' => null,
                'completed_by' => null,
            ]);

            if (
                $order->status === OrderStatus::Completed
                && $order->intentions()
                    ->whereNotIn('status', [IntentionStatus::Completed->value, IntentionStatus::Cancelled->value])
                    ->exists()
            ) {
                $order->update([
                    'status' => OrderStatus::Confirmed,
                    'completed_at' => null,
                ]);
            }

            $this->audit->record(
                action: 'reading_list.entry_completion_corrected',
                target: $lockedEntry,
                churchId: $lockedList->church_id,
                reason: $reason,
                beforeData: $before,
                afterData: [
                    'entry_status' => ReadingListEntryStatus::Pending->value,
                    'intention_status' => IntentionStatus::Confirmed->value,
                    'order_status' => $order->refresh()->status->value,
                ],
                actorUserId: $actor->id,
            );

            return $lockedEntry->refresh();
        });
    }
}
