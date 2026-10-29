<?php

namespace App\Actions\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentExceptionStatus;
use App\Enums\PaymentExceptionType;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentEvidence;
use App\Models\PaymentException;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubmitPaymentEvidenceAction
{
    public const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public const MAX_BYTES = 5242880; // 5 MB

    public function __construct(
        protected AuditService $audit,
    ) {}

    /**
     * @param  array{
     *     reference_number: string,
     *     submitted_amount: string|float|int,
     *     evidence_file: UploadedFile,
     * }  $data
     */
    public function execute(Payment $payment, array $data, ?User $actor = null): Payment
    {
        $file = $data['evidence_file'];

        // 1. Validate file MIME and size using server-side inspection
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'evidence_file' => ['File upload failed or corrupted.'],
            ]);
        }

        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages([
                'evidence_file' => ['Invalid file type. Only JPEG, PNG, and WebP images are permitted for payment screenshots.'],
            ]);
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw ValidationException::withMessages([
                'evidence_file' => ['The uploaded screenshot exceeds the maximum allowed file size of 5 MB.'],
            ]);
        }

        $rawRef = trim($data['reference_number']);
        $normalizedRef = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $rawRef));
        if (empty($normalizedRef)) {
            throw ValidationException::withMessages([
                'reference_number' => ['A valid GCash transaction reference number is required.'],
            ]);
        }

        $submittedAmount = number_format((float) $data['submitted_amount'], 2, '.', '');

        return DB::transaction(function () use ($payment, $file, $rawRef, $normalizedRef, $submittedAmount, $actor, $mime) {
            $payment->lockForUpdate();
            $order = $payment->order()->lockForUpdate()->first();
            $mass = $order->massSchedule;

            // 2. Store file on private disk with randomized key (never expose publicly)
            $ext = $file->getClientOriginalExtension() ?: 'png';
            $randomKey = 'evidence/'.Str::uuid().'.'.$ext;
            Storage::disk('private')->putFileAs('', $file, $randomKey);

            // Record payment evidence
            $evidence = PaymentEvidence::create([
                'payment_id' => $payment->id,
                'church_id' => $payment->church_id,
                'storage_disk' => 'private',
                'storage_key' => $randomKey,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'size_bytes' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'uploaded_by_user_id' => $actor?->id,
                'uploaded_by_guest' => $actor === null,
                'evidence_status' => 'active',
            ]);

            // Determine status and check for exception conditions
            $nextPaymentStatus = PaymentStatus::PendingVerification;
            $exceptionsCreated = [];

            // Duplicate reference check (Rule 16: Duplicate reference creates a review flag)
            $isDuplicate = Payment::where('normalized_reference', $normalizedRef)
                ->where('id', '!=', $payment->id)
                ->exists();

            if ($isDuplicate) {
                $nextPaymentStatus = PaymentStatus::NeedsReview;
                $exception = PaymentException::create([
                    'payment_id' => $payment->id,
                    'church_id' => $payment->church_id,
                    'type' => PaymentExceptionType::DuplicateReference,
                    'status' => PaymentExceptionStatus::Open,
                    'priority' => 'high',
                    'description' => "GCash reference '{$rawRef}' has already been claimed on another payment.",
                    'opened_by' => $actor?->id,
                ]);
                $exceptionsCreated[] = $exception;
            }

            // Late payment check (Rule 30: Payment after cutoff held for review rather than silently discarded)
            if ($mass && now()->gte($mass->booking_cutoff_at)) {
                $nextPaymentStatus = PaymentStatus::NeedsReview;
                $exception = PaymentException::create([
                    'payment_id' => $payment->id,
                    'church_id' => $payment->church_id,
                    'type' => PaymentExceptionType::LatePayment,
                    'status' => PaymentExceptionStatus::Open,
                    'priority' => 'normal',
                    'description' => 'Payment evidence submitted after Mass submission cutoff deadline.',
                    'opened_by' => $actor?->id,
                ]);
                $exceptionsCreated[] = $exception;
            }

            // Update payment record
            $payment->update([
                'submitted_reference' => $rawRef,
                'normalized_reference' => $normalizedRef,
                'submitted_amount' => $submittedAmount,
                'status' => $nextPaymentStatus,
            ]);

            // Transition order status to payment_under_review (Rule 12: Evidence moves to review, NEVER verified)
            $order->update([
                'status' => OrderStatus::PaymentUnderReview,
            ]);

            $this->audit->record(
                action: 'payment.evidence_submitted',
                target: $payment,
                churchId: $payment->church_id,
                reason: "GCash evidence submitted with reference {$rawRef}.",
                afterData: [
                    'submitted_reference' => $rawRef,
                    'submitted_amount' => $submittedAmount,
                    'status' => $nextPaymentStatus->value,
                    'exceptions_count' => count($exceptionsCreated),
                ],
                actorUserId: $actor?->id,
                actorType: $actor ? 'user' : 'guest',
            );

            return $payment;
        });
    }
}
