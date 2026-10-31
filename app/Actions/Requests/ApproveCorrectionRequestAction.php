<?php

namespace App\Actions\Requests;

use App\Enums\CorrectionRequestStatus;
use App\Enums\IntentionStatus;
use App\Enums\OrderStatus;
use App\Enums\ReadingListStatus;
use App\Models\CorrectionRequest;
use App\Models\Intention;
use App\Models\Order;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveCorrectionRequestAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(CorrectionRequest $correction, User $actor, string $reason): CorrectionRequest
    {
        if (! $actor->isCentralAdmin()) {
            throw new AuthorizationException('Only Central Administrators can approve correction requests.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => [__('A decision reason is required.')],
            ]);
        }
        $reason = trim($reason);

        return DB::transaction(function () use ($correction, $actor, $reason) {
            $order = Order::query()
                ->whereKey($correction->order_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedCorrection = CorrectionRequest::query()->whereKey($correction->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedCorrection->status, [CorrectionRequestStatus::Submitted, CorrectionRequestStatus::UnderReview], true)) {
                throw ValidationException::withMessages([
                    'correction' => [__('Only a submitted or under-review correction request can be approved.')],
                ]);
            }
            $beforeRequestStatus = $lockedCorrection->status->value;
            $intention = Intention::query()
                ->whereKey($lockedCorrection->intention_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $lockedCorrection->order_id !== $order->id
                || $lockedCorrection->church_id !== $order->church_id
                || $intention->order_id !== $order->id
                || $intention->church_id !== $order->church_id
                || $intention->mass_schedule_id !== $order->mass_schedule_id
            ) {
                throw ValidationException::withMessages([
                    'correction' => [__('The correction request does not match its order and intention.')],
                ]);
            }

            if (! in_array($order->status, [OrderStatus::Confirmed, OrderStatus::Completed], true)) {
                throw ValidationException::withMessages([
                    'correction' => [__('Only a confirmed or completed order can receive an approved correction.')],
                ]);
            }

            $readingList = ReadingList::query()
                ->where('church_id', $order->church_id)
                ->where('mass_schedule_id', $order->mass_schedule_id)
                ->lockForUpdate()
                ->first();

            if (! $readingList) {
                throw ValidationException::withMessages([
                    'correction' => [__('A reading list must exist before a correction can be applied.')],
                ]);
            }

            if ($readingList->status === ReadingListStatus::Completed) {
                throw ValidationException::withMessages([
                    'correction' => [__('Corrections cannot be approved after the reading list is completed.')],
                ]);
            }

            if ($readingList->status === ReadingListStatus::Finalized) {
                throw ValidationException::withMessages([
                    'correction' => [__('The assigned lector must reopen the finalized reading list before this correction can be approved.')],
                ]);
            }

            if (! in_array($readingList->status, [ReadingListStatus::Preparing, ReadingListStatus::Reopened], true)) {
                throw ValidationException::withMessages([
                    'correction' => [__('The reading list is not in a state that permits corrections.')],
                ]);
            }

            if (! in_array($intention->status, [IntentionStatus::Confirmed, IntentionStatus::CorrectionPending, IntentionStatus::Completed], true)) {
                throw ValidationException::withMessages([
                    'correction' => [__('Only a confirmed, correction-pending, or completed intention can be corrected.')],
                ]);
            }

            $entry = ReadingListEntry::query()
                ->where('reading_list_id', $readingList->id)
                ->where('intention_id', $intention->id)
                ->where('church_id', $order->church_id)
                ->where('mass_schedule_id', $order->mass_schedule_id)
                ->lockForUpdate()
                ->first();

            if (! $entry) {
                throw ValidationException::withMessages([
                    'correction' => [__('The intention is not part of this reading list.')],
                ]);
            }

            $oldValues = $lockedCorrection->old_values;
            $requestedValues = $lockedCorrection->requested_values;
            if (
                ! is_array($oldValues)
                || ! is_array($requestedValues)
                || ! isset($oldValues['person_or_family_name'], $oldValues['wording'])
                || ! isset($requestedValues['person_or_family_name'], $requestedValues['wording'])
                || ! is_string($oldValues['person_or_family_name'])
                || ! is_string($oldValues['wording'])
                || ! is_string($requestedValues['person_or_family_name'])
                || ! is_string($requestedValues['wording'])
                || $intention->person_or_family_name !== $oldValues['person_or_family_name']
                || $intention->wording !== $oldValues['wording']
            ) {
                throw ValidationException::withMessages([
                    'correction' => [__('The intention changed after this correction was submitted; review the current wording before approving.')],
                ]);
            }

            $beforeStatus = $intention->status;
            $intention->update([
                'person_or_family_name' => $requestedValues['person_or_family_name'],
                'wording' => $requestedValues['wording'],
                'status' => $beforeStatus === IntentionStatus::CorrectionPending
                    ? IntentionStatus::Confirmed
                    : $beforeStatus,
            ]);

            $lockedCorrection->update([
                'status' => CorrectionRequestStatus::Approved,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'decision_reason' => $reason,
            ]);

            $this->audit->record(
                action: 'request.correction_approved',
                target: $lockedCorrection,
                churchId: $order->church_id,
                reason: $reason,
                beforeData: [
                    'request_status' => $beforeRequestStatus,
                    'intention_status' => $beforeStatus->value,
                ],
                afterData: [
                    'request_status' => CorrectionRequestStatus::Approved->value,
                    'intention_status' => $intention->fresh()->status->value,
                    'reading_list_id' => $readingList->id,
                    'reading_list_version' => $readingList->version,
                ],
                actorUserId: $actor->id,
            );

            return $lockedCorrection->refresh();
        });
    }
}
