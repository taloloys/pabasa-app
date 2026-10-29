<?php

namespace App\Enums;

enum PricingStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Retired = 'retired';
}
