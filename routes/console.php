<?php

use App\Actions\ReadingLists\FinalizeDueReadingListsAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reading-lists:finalize-due', function (FinalizeDueReadingListsAction $action) {
    $processed = $action->execute();
    $this->info("Processed {$processed} due reading list(s).");
})->purpose('Finalize eligible reading lists after their configured deadline');

Schedule::command('reading-lists:finalize-due')
    ->everyMinute()
    ->withoutOverlapping();
