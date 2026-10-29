<?php

namespace App\Enums;

enum PaymentExceptionStatus: string
{
    case Open = 'open';
    case Investigating = 'investigating';
    case Resolved = 'resolved';
    case Closed = 'closed';
}
