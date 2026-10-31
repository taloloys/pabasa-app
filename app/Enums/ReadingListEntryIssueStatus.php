<?php

namespace App\Enums;

enum ReadingListEntryIssueStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
}
