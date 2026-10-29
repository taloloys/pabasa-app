<?php

namespace App\Enums;

enum PaymentExceptionType: string
{
    case Unmatched = 'unmatched';
    case Underpayment = 'underpayment';
    case Overpayment = 'overpayment';
    case DuplicateReference = 'duplicate_reference';
    case LatePayment = 'late_payment';
    case UnclearEvidence = 'unclear_evidence';
    case Other = 'other';
}
