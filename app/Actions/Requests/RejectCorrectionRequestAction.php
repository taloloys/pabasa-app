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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectCorrectionRequestAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(CorrectionRequest $correction, User $actor, string $reason): CorrectionRequest
    {
        if (! $actor->isCentralAdmin()) {
            throw new AuthorizationException('Only Central Administrators can reject correction requests.');
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
                    'correction' => [__('Only a submitted or under-review correction request can be rejected.')],
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
            ) {
                throw ValidationException::withMessages([
                    'correction' => [__('The correction request does not match its order and intention.')],
                ]);
            }

            if ($intention->status === IntentionStatus::CorrectionPending) {
                $restoredStatus = match ($order->status) {
                    OrderStatus::Confirmed => IntentionStatus::Confirmed,
                    OrderStatus::Completed => IntentionStatus::Completed,
                    OrderStatus::Cancelled => IntentionStatus::Cancelled,
                    default => throw ValidationException::withMessages([
                        'correction' => [__('The order state does not permit restoring this intention after rejection.')],
                    ]),
                };
                $intention->update(['status' => $restoredStatus]);
            }

            $lockedCorrection->update([
                'status' => CorrectionRequestStatus::Rejected,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'decision_reason' => $reason,
            ]);

            $this->audit->record(
                action: 'request.correction_rejected',
                target: $lockedCorrection,
                churchId: $order->church_id,
                reason: $reason,
                beforeData: ['request_status' => $beforeRequestStatus],
                afterData: [
                    'request_status' => CorrectionRequestStatus::Rejected->value,
                    'intention_status' => $intention->fresh()->status->value,
                ],
                actorUserId: $actor->id,
            );

            return $lockedCorrection->refresh();
        });
    }
}
