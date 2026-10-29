<?php

namespace App\Actions\Masses;

use App\Enums\BookingStatus;
use App\Enums\MassSeriesStatus;
use App\Enums\MassStatus;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\MassSchedule;
use App\Models\MassSeries;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Booking\ConflictDetectionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GenerateMassSeriesOccurrencesAction
{
    public function __construct(
        protected AuditService $audit,
        protected ConflictDetectionService $conflictDetector,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     description?: ?string,
     *     recurrence_rule: array{
     *         days_of_week: array<int>, // 0=Sunday, 1=Monday... 6=Saturday
     *         time: string, // e.g. "08:00"
     *         duration_minutes?: int,
     *     },
     *     starts_on: string,
     *     ends_on?: ?string,
     *     category_id?: ?int,
     *     language?: string,
     *     location_label?: ?string,
     *     resource_key?: ?string,
     *     booking_opens_days_before?: int,
     *     booking_cutoff_hours_before?: int,
     * }  $data
     * @return array{series: MassSeries, occurrences_count: int}
     */
    public function execute(Church $church, array $data, User $actor): array
    {
        $startsOn = Carbon::parse($data['starts_on'])->startOfDay();
        $endsOn = ! empty($data['ends_on'])
            ? Carbon::parse($data['ends_on'])->endOfDay()
            : $startsOn->copy()->addWeeks(4)->endOfDay();

        if ($startsOn->gt($endsOn)) {
            throw ValidationException::withMessages([
                'starts_on' => ['Start date must be before or equal to the end date.'],
            ]);
        }

        $recurrence = $data['recurrence_rule'];
        $daysOfWeek = $recurrence['days_of_week'] ?? [];
        if (empty($daysOfWeek)) {
            throw ValidationException::withMessages([
                'recurrence_rule.days_of_week' => ['At least one day of the week must be selected.'],
            ]);
        }

        $timeStr = $recurrence['time'] ?? '08:00';
        $durationMinutes = $recurrence['duration_minutes'] ?? 60;
        $bookingOpensDaysBefore = $data['booking_opens_days_before'] ?? 7;
        $bookingCutoffHoursBefore = $data['booking_cutoff_hours_before'] ?? 2;
        $tz = $church->timezone ?: 'Asia/Manila';

        $categoryId = $data['category_id'] ?? null;
        $categorySnapshot = null;
        if ($categoryId) {
            $cat = ChurchCategory::find($categoryId);
            $categorySnapshot = $cat?->label;
        }

        return DB::transaction(function () use (
            $church,
            $data,
            $actor,
            $startsOn,
            $endsOn,
            $daysOfWeek,
            $timeStr,
            $durationMinutes,
            $bookingOpensDaysBefore,
            $bookingCutoffHoursBefore,
            $tz,
            $categorySnapshot
        ) {
            $series = MassSeries::create([
                'church_id' => $church->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'recurrence_rule' => $data['recurrence_rule'],
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $endsOn->toDateString(),
                'timezone' => $tz,
                'category_id' => $data['category_id'] ?? null,
                'location_label' => $data['location_label'] ?? null,
                'resource_key' => $data['resource_key'] ?? null,
                'booking_opens_days_before' => $bookingOpensDaysBefore,
                'booking_cutoff_hours_before' => $bookingCutoffHoursBefore,
                'status' => MassSeriesStatus::Active,
                'created_by' => $actor->id,
            ]);

            $currentDay = $startsOn->copy();
            $occurrencesCount = 0;

            [$hour, $minute] = explode(':', $timeStr);

            while ($currentDay->lte($endsOn)) {
                // Carbon dayOfWeek: 0 = Sunday, 1 = Monday, ... 6 = Saturday
                if (in_array($currentDay->dayOfWeek, $daysOfWeek, true)) {
                    $occurrenceStart = $currentDay->copy()->setTime((int) $hour, (int) $minute, 0);
                    $occurrenceEnd = $occurrenceStart->copy()->addMinutes($durationMinutes);

                    $bookingOpensAt = $occurrenceStart->copy()->subDays($bookingOpensDaysBefore);
                    $bookingCutoffAt = $occurrenceStart->copy()->subHours($bookingCutoffHoursBefore);

                    // Skip if resource conflict exists
                    if (! empty($data['resource_key'])) {
                        $conflict = $this->conflictDetector->checkResourceConflict(
                            church: $church,
                            startsAt: $occurrenceStart,
                            endsAt: $occurrenceEnd,
                            resourceKey: $data['resource_key'],
                        );

                        if ($conflict['has_conflict']) {
                            $currentDay->addDay();

                            continue;
                        }
                    }

                    MassSchedule::create([
                        'church_id' => $church->id,
                        'mass_series_id' => $series->id,
                        'category_id' => $data['category_id'] ?? null,
                        'category_label_snapshot' => $categorySnapshot,
                        'title' => $data['title'],
                        'starts_at' => $occurrenceStart,
                        'ends_at' => $occurrenceEnd,
                        'timezone' => $tz,
                        'language' => $data['language'] ?? 'English',
                        'booking_status' => BookingStatus::Open,
                        'status' => MassStatus::Scheduled,
                        'booking_opens_at' => $bookingOpensAt,
                        'booking_cutoff_at' => $bookingCutoffAt,
                        'location_label' => $data['location_label'] ?? null,
                        'resource_key' => $data['resource_key'] ?? null,
                        'created_by' => $actor->id,
                        'updated_by' => $actor->id,
                    ]);

                    $occurrencesCount++;
                }

                $currentDay->addDay();
            }

            $this->audit->record(
                action: 'mass_series.created',
                target: $series,
                churchId: $church->id,
                reason: "Recurring Mass series '{$series->title}' generated with {$occurrencesCount} occurrences.",
                afterData: array_merge($series->toArray(), ['occurrences_count' => $occurrencesCount]),
                actorUserId: $actor->id,
            );

            return [
                'series' => $series,
                'occurrences_count' => $occurrencesCount,
            ];
        });
    }
}
