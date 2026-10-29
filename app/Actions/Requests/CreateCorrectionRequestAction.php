<?php

namespace App\Actions\Requests;

use App\Enums\CorrectionRequestStatus;
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
        if ($intention->order_id !== $order->id) {
            throw ValidationException::withMessages([
                'intention_id' => ['The intention does not belong to this order.'],
            ]);
        }

        return DB::transaction(function () use ($order, $intention, $newValues, $actor) {
            $oldValues = [
                'person_or_family_name' => $intention->person_or_family_name,
                'wording' => $intention->wording,
            ];

            $request = CorrectionRequest::create([
                'order_id' => $order->id,
                'intention_id' => $intention->id,
                'church_id' => $order->church_id,
                'requested_by_user_id' => $actor?->id,
                'request_source' => $actor ? 'user' : 'guest',
                'old_values' => $oldValues,
                'requested_values' => $newValues,
                'status' => CorrectionRequestStatus::Submitted,
            ]);

            $this->audit->record(
                action: 'request.correction_submitted',
                target: $request,
                churchId: $order->church_id,
                reason: "Correction requested for intention #{$intention->id}.",
                afterData: $request->toArray(),
                actorUserId: $actor?->id,
                actorType: $actor ? 'user' : 'guest',
            );

            return $request;
        });
    }
}
