<?php

namespace App\Actions\Requests;

use App\Enums\CorrectionRequestStatus;
use App\Enums\IntentionStatus;
use App\Enums\OrderStatus;
use App\Models\CorrectionRequest;
use App\Models\Intention;
use App\Models\Order;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCorrectionRequestAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(Order $order, Intention $intention, array $newValues, ?User $actor = null): CorrectionRequest
    {
        return DB::transaction(function () use ($order, $intention, $newValues, $actor) {
            if (
                ! isset($newValues['person_or_family_name'], $newValues['wording'])
                || ! is_string($newValues['person_or_family_name'])
                || ! is_string($newValues['wording'])
            ) {
                throw ValidationException::withMessages([
                    'correction' => [__('A correction must include a name and intention wording.')],
                ]);
            }

            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lockedIntention = Intention::query()->whereKey($intention->id)->lockForUpdate()->firstOrFail();

            if (
                $lockedIntention->order_id !== $lockedOrder->id
                || $lockedIntention->church_id !== $lockedOrder->church_id
                || $lockedIntention->mass_schedule_id !== $lockedOrder->mass_schedule_id
            ) {
                throw ValidationException::withMessages([
                    'intention_id' => [__('The intention does not belong to this order.')],
                ]);
            }

            if (
                ! in_array($lockedOrder->status, [OrderStatus::Confirmed, OrderStatus::Completed], true)
                || ! in_array($lockedIntention->status, [IntentionStatus::Confirmed, IntentionStatus::Completed], true)
            ) {
                throw ValidationException::withMessages([
                    'intention_id' => [__('Only a confirmed or completed intention can have a correction request.')],
                ]);
            }
            $beforeIntentionStatus = $lockedIntention->status->value;

            $hasOpenRequest = CorrectionRequest::query()
                ->where('intention_id', $lockedIntention->id)
                ->whereIn('status', [
                    CorrectionRequestStatus::Submitted->value,
                    CorrectionRequestStatus::UnderReview->value,
                ])
                ->exists();

            if ($hasOpenRequest) {
                throw ValidationException::withMessages([
                    'intention_id' => [__('A correction request is already awaiting review for this intention.')],
                ]);
            }

            $oldValues = [
                'person_or_family_name' => $lockedIntention->person_or_family_name,
                'wording' => $lockedIntention->wording,
            ];

            $request = CorrectionRequest::create([
                'order_id' => $lockedOrder->id,
                'intention_id' => $lockedIntention->id,
                'church_id' => $lockedOrder->church_id,
                'requested_by_user_id' => $actor?->id,
                'request_source' => $actor ? 'user' : 'guest',
                'old_values' => $oldValues,
                'requested_values' => $newValues,
                'status' => CorrectionRequestStatus::Submitted,
            ]);

            if ($lockedIntention->status === IntentionStatus::Confirmed) {
                $lockedIntention->update(['status' => IntentionStatus::CorrectionPending]);
            }

            $this->audit->record(
                action: 'request.correction_submitted',
                target: $request,
                churchId: $lockedOrder->church_id,
                reason: "Correction requested for intention #{$lockedIntention->id}.",
                beforeData: ['intention_status' => $beforeIntentionStatus],
                afterData: [
                    'correction_request_id' => $request->id,
                    'intention_status' => $lockedIntention->fresh()->status->value,
                    'request_status' => CorrectionRequestStatus::Submitted->value,
                ],
                actorUserId: $actor?->id,
                actorType: $actor ? 'user' : 'guest',
            );

            return $request;
        });
    }
}
