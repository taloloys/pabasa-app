<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PendingPayment = 'pending_payment';
    case PendingVerification = 'pending_verification';
    case NeedsReview = 'needs_review';
    case Rejected = 'rejected';
    case ResolutionRequired = 'resolution_required';
    case Verified = 'verified';
    case RefundPending = 'refund_pending';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::PendingVerification => 'Pending Verification',
            self::NeedsReview => 'Needs Review',
            self::Rejected => 'Rejected',
            self::ResolutionRequired => 'Resolution Required',
            self::Verified => 'Verified',
            self::RefundPending => 'Refund Pending',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Refunded => 'Refunded',
        };
    }
}
