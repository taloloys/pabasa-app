<?php

namespace App\Enums;

enum IntentionStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case Confirmed = 'confirmed';
    case CorrectionPending = 'correction_pending';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Awaiting Payment',
            self::Confirmed => 'Confirmed',
            self::CorrectionPending => 'Correction Pending',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }
}
