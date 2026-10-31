<?php

namespace App\Actions\Payments;

use App\Enums\IntentionStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentExceptionStatus;
use App\Enums\PaymentExceptionType;
use App\Enums\PaymentStatus;
use App\Enums\ReadingListStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentException;
use App\Models\ReadingList;
use App\Models\ReadingListEntry;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifyPaymentAction
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    /**
     * @param  array{
     *     verified_amount?: string|float|null,
     *     notes?: ?string,
     *     force_exception_resolution?: bool,
     * }  $options
     *
     * @throws AuthorizationException|ValidationException
     */
    public function execute(Payment $payment, User $admin, array $options = []): Payment
    {
        // Spec rule: Central Admin ONLY
        if (! $admin->isCentralAdmin()) {
            throw new AuthorizationException('Only Central Administrators are authorized to verify payments.');
        }

        return DB::transaction(function () use ($payment, $admin, $options) {
            /** @var Payment $lockedPayment */
            $lockedPayment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
            $order = $lockedPayment->order()->lockForUpdate()->firstOrFail();

            // Rule 29: Check if already verified (idempotency check)
            if ($lockedPayment->status === PaymentStatus::Verified) {
                // Ensure reading list is populated idempotently even on retried verify
                $this->populateReadingListIdempotently($order, $admin);

                return $lockedPayment;
            }

            $expected = (string) $lockedPayment->expected_amount;
            $submitted = (string) ($lockedPayment->submitted_amount ?? '0.00');
            $verifiedAmount = isset($options['verified_amount'])
                ? number_format((float) $options['verified_amount'], 2, '.', '')
                : $submitted;

            // Exception check: Underpayment
            if (bccomp($verifiedAmount, $expected, 2) < 0 && empty($options['force_exception_resolution'])) {
                PaymentException::firstOrCreate([
                    'payment_id' => $lockedPayment->id,
                    'church_id' => $lockedPayment->church_id,
                    'type' => PaymentExceptionType::Underpayment,
                ], [
                    'status' => PaymentExceptionStatus::Open,
                    'priority' => 'high',
                    'description' => "Submitted amount (PHP {$verifiedAmount}) is less than expected (PHP {$expected}).",
                    'opened_by' => $admin->id,
                ]);

                $lockedPayment->update(['status' => PaymentStatus::NeedsReview]);

                return $lockedPayment;
            }

            // Exception check: Overpayment
            if (bccomp($verifiedAmount, $expected, 2) > 0) {
                PaymentException::firstOrCreate([
                    'payment_id' => $lockedPayment->id,
                    'church_id' => $lockedPayment->church_id,
                    'type' => PaymentExceptionType::Overpayment,
                ], [
                    'status' => PaymentExceptionStatus::Open,
                    'priority' => 'normal',
                    'description' => "Submitted amount (PHP {$verifiedAmount}) exceeds expected (PHP {$expected}).",
                    'opened_by' => $admin->id,
                ]);
            }

            // Exception check: Duplicate Reference
            $duplicateCount = Payment::where('normalized_reference', $lockedPayment->normalized_reference)
                ->where('id', '!=', $lockedPayment->id)
                ->where('status', PaymentStatus::Verified->value)
                ->count();

            if ($duplicateCount > 0 && empty($options['force_exception_resolution'])) {
                PaymentException::firstOrCreate([
                    'payment_id' => $lockedPayment->id,
                    'church_id' => $lockedPayment->church_id,
                    'type' => PaymentExceptionType::DuplicateReference,
                ], [
                    'status' => PaymentExceptionStatus::Open,
                    'priority' => 'high',
                    'description' => 'GCash reference has already been verified for another transaction.',
                    'opened_by' => $admin->id,
                ]);

                $lockedPayment->update(['status' => PaymentStatus::NeedsReview]);

                return $lockedPayment;
            }

            // Resolve any open exceptions if forced resolution is active
            if (! empty($options['force_exception_resolution'])) {
                $lockedPayment->exceptions()
                    ->where('status', PaymentExceptionStatus::Open->value)
                    ->update([
                        'status' => PaymentExceptionStatus::Resolved->value,
                        'resolved_by' => $admin->id,
                        'resolved_at' => now(),
                        'resolution_reason' => $options['notes'] ?? 'Resolved during administrative verification override.',
                    ]);
            }

            // Transition payment state
            $lockedPayment->update([
                'status' => PaymentStatus::Verified,
                'verified_amount' => $verifiedAmount,
                'verified_by' => $admin->id,
                'verified_at' => now(),
                'resolution_notes' => $options['notes'] ?? null,
            ]);

            // Transition order and intentions in the same transaction
            $order->update([
                'status' => OrderStatus::Confirmed,
                'confirmed_at' => now(),
            ]);

            $order->intentions()->update([
                'status' => IntentionStatus::Confirmed,
            ]);

            // Rule 19: Retried verification request does not duplicate reading-list entries
            $this->populateReadingListIdempotently($order, $admin);

            // Audit verification event
            $this->audit->record(
                action: 'payment.verified',
                target: $lockedPayment,
                churchId: $lockedPayment->church_id,
                reason: "Payment verified by Central Admin. Verified amount: PHP {$verifiedAmount}.",
                afterData: [
                    'order_id' => $order->id,
                    'verified_amount' => $verifiedAmount,
                    'reference' => $lockedPayment->submitted_reference,
                    'status' => PaymentStatus::Verified->value,
                ],
                actorUserId: $admin->id,
            );

            return $lockedPayment;
        });
    }

    /**
     * Idempotently create reading list entries for all confirmed intentions in this order.
     */
    protected function populateReadingListIdempotently(Order $order, User $admin): void
    {
        $readingList = ReadingList::firstOrCreate(
            ['mass_schedule_id' => $order->mass_schedule_id],
            [
                'church_id' => $order->church_id,
                'status' => ReadingListStatus::Preparing,
                'version' => 1,
            ]
        );
        $readingList = ReadingList::query()
            ->whereKey($readingList->id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($readingList->church_id !== $order->church_id) {
            throw ValidationException::withMessages([
                'reading_list' => "The reading list does not belong to this order's church.",
            ]);
        }

        if (in_array($readingList->status, [ReadingListStatus::Finalized, ReadingListStatus::Completed], true)) {
            return;
        }

        $intentions = $order->intentions()->where('status', IntentionStatus::Confirmed->value)->get();
        if ($intentions->isEmpty()) {
            return;
        }

        // Fetch existing entries in one single query to eliminate per-intention checks
        $existingIntentionIds = ReadingListEntry::where('reading_list_id', $readingList->id)
            ->whereIn('intention_id', $intentions->pluck('id'))
            ->pluck('intention_id')
            ->flip()
            ->all();

        // Calculate max position once using the (reading_list_id, position) composite index
        $maxPos = ReadingListEntry::where('reading_list_id', $readingList->id)->max('position') ?? 0;

        foreach ($intentions as $intention) {
            if (isset($existingIntentionIds[$intention->id])) {
                continue;
            }

            $maxPos++;

            ReadingListEntry::create([
                'reading_list_id' => $readingList->id,
                'church_id' => $order->church_id,
                'mass_schedule_id' => $order->mass_schedule_id,
                'intention_id' => $intention->id,
                'position' => $maxPos,
                'status' => 'pending',
            ]);
        }
    }
}
