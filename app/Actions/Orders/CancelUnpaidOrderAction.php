<?php

namespace App\Actions\Orders;

use App\Enums\IntentionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelUnpaidOrderAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    /**
     * Cancel an unverified, unpaid order that is awaiting payment.
     *
     * @throws ValidationException
     */
    public function execute(Order $order, ?User $actor = null, ?string $reason = null): Order
    {
        if ($order->status !== OrderStatus::AwaitingPayment) {
            throw ValidationException::withMessages([
                'order' => ['Only orders awaiting payment can be cancelled directly.'],
            ]);
        }

        $reason = $reason ?: 'Order cancelled by customer before payment.';

        return DB::transaction(function () use ($order, $actor, $reason) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::AwaitingPayment) {
                throw ValidationException::withMessages([
                    'order' => ['This order has already been updated or processed.'],
                ]);
            }

            $lockedOrder->update([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
            ]);

            $lockedOrder->intentions()->update([
                'status' => IntentionStatus::Cancelled,
            ]);

            // If there's an associated pending payment, update its status to rejected
            $payment = $lockedOrder->latestPayment;
            if ($payment && $payment->status === PaymentStatus::PendingPayment) {
                $payment->update([
                    'status' => PaymentStatus::Rejected,
                    'rejection_reason' => $reason,
                    'rejected_at' => now(),
                ]);
            }

            // Revoke active guest credentials if any
            $lockedOrder->guestAccess()
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $this->audit->record(
                action: 'order.cancelled',
                target: $lockedOrder,
                churchId: $lockedOrder->church_id,
                reason: $reason,
                afterData: [
                    'order_number' => $lockedOrder->order_number,
                    'status' => OrderStatus::Cancelled->value,
                    'cancelled_at' => $lockedOrder->cancelled_at?->toIso8601String(),
                ],
                actorUserId: $actor?->id,
                actorType: $actor ? 'registered_user' : 'guest',
            );

            return $lockedOrder;
        });
    }
}

