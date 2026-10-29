<?php

namespace App\Actions\Requests;

use App\Enums\ReschedulingRequestStatus;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\ReschedulingRequest;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateReschedulingRequestAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(Order $order, int $targetMassScheduleId, string $reason, ?int $intentionId = null, ?User $actor = null): ReschedulingRequest
    {
        $targetMass = MassSchedule::findOrFail($targetMassScheduleId);

        if ($targetMass->church_id !== $order->church_id) {
            throw ValidationException::withMessages([
                'requested_mass_schedule_id' => ['Target Mass must belong to the same parish.'],
            ]);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => ['A reason for rescheduling must be provided.'],
            ]);
        }

        return DB::transaction(function () use ($order, $targetMass, $reason, $intentionId, $actor) {
            // Rule 27: Rescheduling request does not automatically move an order
            $request = ReschedulingRequest::create([
                'order_id' => $order->id,
                'intention_id' => $intentionId,
                'church_id' => $order->church_id,
                'requested_by_user_id' => $actor?->id,
                'request_source' => $actor ? 'user' : 'guest',
                'requested_mass_schedule_id' => $targetMass->id,
                'reason' => $reason,
                'status' => ReschedulingRequestStatus::Submitted,
                'payment_implication_status' => 'not_assessed',
            ]);

            $this->audit->record(
                action: 'request.rescheduling_submitted',
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
