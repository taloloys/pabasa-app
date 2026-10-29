<?php

namespace App\Enums;

enum ChurchStatus: string
{
    case Draft = 'draft';
    case PendingVerification = 'pending_verification';
    case Verified = 'verified';
    case Published = 'published';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';
}
