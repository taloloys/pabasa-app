<?php

namespace App\Enums;

enum MassSeriesStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
}
