<?php

namespace App\Actions\Requests;

use App\Enums\CancellationRequestStatus;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCancellationRequestAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(Order $order, string $reason, ?int $intentionId = null, ?User $actor = null): CancellationRequest
    {
        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => ['A cancellation reason must be provided.'],
            ]);
        }

        return DB::transaction(function () use ($order, $reason, $intentionId, $actor) {
            // Rule 26: Cancellation request creates a review case and does NOT automatically cancel/refund the order
            $request = CancellationRequest::create([
                'order_id' => $order->id,
                'intention_id' => $intentionId,
                'church_id' => $order->church_id,
                'requested_by_user_id' => $actor?->id,
                'request_source' => $actor ? 'user' : 'guest',
                'reason' => $reason,
                'status' => CancellationRequestStatus::Submitted,
                'refund_required' => null,
            ]);

            $this->audit->record(
                action: 'request.cancellation_submitted',
                target: $request,
                churchId: $order->church_id,
                reason: $reason,
                afterData: $request->toArray(),
                actorUserId: $actor?->id,
                actorType: $actor ? 'user' : 'guest',
            );

            return $request;
        });
    }
}
