<?php

namespace App\Enums;

enum ReadingListEntryStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case IssueReported = 'issue_reported';
    case Excluded = 'excluded';
}
