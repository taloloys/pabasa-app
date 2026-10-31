<?php

namespace App\Enums;

enum ReadingListStatus: string
{
    case Preparing = 'preparing';
    case Finalized = 'finalized';
    case Reopened = 'reopened';
    case Completed = 'completed';
}
