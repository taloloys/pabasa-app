<?php

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectPaymentAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    public function execute(Payment $payment, User $admin, string $reason): Payment
    {
        if (! $admin->isCentralAdmin()) {
            throw new AuthorizationException('Only Central Administrators are authorized to reject payments.');
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => ['A valid rejection reason must be provided.'],
            ]);
        }

        return DB::transaction(function () use ($payment, $admin, $reason) {
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
            $order = $lockedPayment->order()->lockForUpdate()->firstOrFail();

            $before = $lockedPayment->only(['status', 'rejection_reason']);

            $lockedPayment->update([
                'status' => PaymentStatus::Rejected,
                'rejected_by' => $admin->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $order->update([
                'status' => OrderStatus::ActionRequired,
            ]);

            $this->audit->record(
                action: 'payment.rejected',
                target: $lockedPayment,
                churchId: $lockedPayment->church_id,
                reason: $reason,
                beforeData: $before,
                afterData: [
                    'status' => PaymentStatus::Rejected->value,
                    'rejection_reason' => $reason,
                ],
                actorUserId: $admin->id,
            );

            return $lockedPayment;
        });
    }
}
