<?php

namespace App\Actions\ReadingLists;

use App\Enums\IntentionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
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

class MarkReadingEntryCompletedAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(ReadingList $readingList, ReadingListEntry $entry, User $actor): ReadingListEntry
    {
        return DB::transaction(function () use ($readingList, $entry, $actor) {
            if ($entry->reading_list_id !== $readingList->id) {
                abort(404);
            }

            $entryIntentionId = ReadingListEntry::query()
                ->where('reading_list_id', $readingList->id)
                ->whereKey($entry->id)
                ->value('intention_id');
            $orderId = Intention::query()->whereKey($entryIntentionId)->value('order_id');
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();

            $lockedList = ReadingList::query()
                ->whereKey($readingList->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('completeEntry', $lockedList);

            $lockedEntry = ReadingListEntry::query()
                ->where('reading_list_id', $lockedList->id)
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedEntry->status === ReadingListEntryStatus::Completed) {
                return $lockedEntry;
            }

            if ($lockedList->status !== ReadingListStatus::Finalized) {
                throw ValidationException::withMessages([
                    'reading_list' => __('Only entries on a finalized reading list can be marked completed.'),
                ]);
            }

            if ($lockedEntry->status !== ReadingListEntryStatus::Pending) {
                throw ValidationException::withMessages([
                    'entry' => __('Only a pending reading list entry can be marked completed.'),
                ]);
            }

            $intention = Intention::query()
                ->whereKey($lockedEntry->intention_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $intention->church_id !== $lockedList->church_id
                || $intention->mass_schedule_id !== $lockedList->mass_schedule_id
                || $order->church_id !== $lockedList->church_id
                || $order->mass_schedule_id !== $lockedList->mass_schedule_id
                || $order->status !== OrderStatus::Confirmed
                || ! $order->payments()->where('status', PaymentStatus::Verified->value)->exists()
                || ! in_array($intention->status, [IntentionStatus::Confirmed, IntentionStatus::CorrectionPending], true)
            ) {
                throw ValidationException::withMessages([
                    'entry' => __('Only a confirmed intention on a confirmed order belonging to this Mass can be completed.'),
                ]);
            }

            $completedAt = now();
            $lockedEntry->update([
                'status' => ReadingListEntryStatus::Completed,
                'completed_at' => $completedAt,
                'completed_by' => $actor->id,
            ]);
            $intention->update(['status' => IntentionStatus::Completed]);

            $this->audit->record(
                action: 'reading_list.entry_completed',
                target: $lockedEntry,
                churchId: $lockedList->church_id,
                reason: 'Assigned lector marked the intention as read.',
                beforeData: ['status' => ReadingListEntryStatus::Pending->value],
                afterData: [
                    'status' => ReadingListEntryStatus::Completed->value,
                    'intention_id' => $intention->id,
                    'completed_at' => $completedAt->toISOString(),
                ],
                actorUserId: $actor->id,
            );

            $entries = ReadingListEntry::query()
                ->where('reading_list_id', $lockedList->id)
                ->lockForUpdate()
                ->get();

            if ($entries->isNotEmpty() && $entries->every(fn (ReadingListEntry $item) => in_array(
                $item->status,
                [ReadingListEntryStatus::Completed, ReadingListEntryStatus::Excluded],
                true,
            ))) {
                $lockedList->update([
                    'status' => ReadingListStatus::Completed,
                    'completed_at' => $completedAt,
                    'completed_by' => $actor->id,
                ]);

                $this->audit->record(
                    action: 'reading_list.completed',
                    target: $lockedList,
                    churchId: $lockedList->church_id,
                    reason: 'Every reading list entry was marked completed.',
                    beforeData: ['status' => ReadingListStatus::Finalized->value],
                    afterData: [
                        'status' => ReadingListStatus::Completed->value,
                        'entry_count' => $entries->count(),
                    ],
                    actorUserId: $actor->id,
                );
            }

            if (
                $order->status === OrderStatus::Confirmed
                && ! $order->intentions()
                    ->whereNotIn('status', [IntentionStatus::Completed->value, IntentionStatus::Cancelled->value])
                    ->exists()
            ) {
                $order->update([
                    'status' => OrderStatus::Completed,
                    'completed_at' => $completedAt,
                ]);

                $this->audit->record(
                    action: 'order.completed',
                    target: $order,
                    churchId: $order->church_id,
                    reason: 'All non-cancelled intentions in the order were completed.',
                    beforeData: ['status' => OrderStatus::Confirmed->value],
                    afterData: ['status' => OrderStatus::Completed->value],
                    actorUserId: $actor->id,
                );
            }

            return $lockedEntry->refresh();
        });
    }
}
