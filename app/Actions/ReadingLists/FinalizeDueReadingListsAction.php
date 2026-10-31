<?php

namespace App\Actions\ReadingLists;

use App\Enums\MassStatus;
use App\Enums\ReadingListStatus;
use App\Models\ReadingList;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class FinalizeDueReadingListsAction
{
    public function __construct(
        protected FinalizeReadingListAction $finalizer,
    ) {}

    public function execute(): int
    {
        $dueAt = now();
        $readingLists = ReadingList::query()
            ->whereIn('status', [
                ReadingListStatus::Preparing->value,
                ReadingListStatus::Reopened->value,
            ])
            ->whereHas('massSchedule', fn ($query) => $query
                ->where('status', MassStatus::Scheduled->value)
                ->whereNotNull('finalization_deadline')
                ->where('finalization_deadline', '<=', $dueAt))
            ->orderBy('id')
            ->get();

        $processed = 0;

        foreach ($readingLists as $readingList) {
            try {
                $this->finalizer->executeScheduled($readingList);
                $processed++;
            } catch (ValidationException $exception) {
                Log::warning('Scheduled reading-list finalization was not applied.', [
                    'reading_list_id' => $readingList->id,
                    'errors' => $exception->errors(),
                ]);
            }
        }

        return $processed;
    }
}
