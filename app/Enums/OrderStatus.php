<?php

namespace App\Enums;

enum OrderStatus: string
{
    case AwaitingPayment = 'awaiting_payment';
    case PaymentUnderReview = 'payment_under_review';
    case Confirmed = 'confirmed';
    case ActionRequired = 'action_required';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Awaiting Payment',
            self::PaymentUnderReview => 'Payment Under Review',
            self::Confirmed => 'Confirmed',
            self::ActionRequired => 'Action Required',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }
}
