<?php

namespace App\Enums;

enum ReadingListIssueResolution: string
{
    case ReturnToPending = 'pending';
    case Exclude = 'excluded';
}
