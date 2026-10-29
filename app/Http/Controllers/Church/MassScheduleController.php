<?php

namespace App\Http\Controllers\Church;

use App\Actions\Masses\CancelMassScheduleAction;
use App\Actions\Masses\CloseMassBookingEarlyAction;
use App\Actions\Masses\CreateMassScheduleAction;
use App\Actions\Masses\ExtendMassCutoffAction;
use App\Actions\Masses\GenerateMassSeriesOccurrencesAction;
use App\Actions\Masses\UpdateMassOccurrenceAction;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\MassSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MassScheduleController extends Controller
{
    public function index(Request $request, Church $church): Response
    {
        $this->authorize('update', $church);

        $masses = MassSchedule::query()
            ->with(['category', 'lectors'])
            ->where('church_id', $church->id)
            ->orderBy('starts_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        $categories = ChurchCategory::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('church_id', $church->id)->orWhere('is_system', true))
            ->get();

        return Inertia::render('Church/Masses/Index', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'timezone' => $church->timezone,
            ],
            'masses' => $masses,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, Church $church, CreateMassScheduleAction $action): RedirectResponse
    {
        $this->authorize('update', $church);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'category_id' => ['nullable', 'exists:church_categories,id'],
            'language' => ['required', 'string', 'max:50'],
            'location_label' => ['nullable', 'string', 'max:100'],
            'resource_key' => ['nullable', 'string', 'max:100'],
            'booking_opens_at' => ['nullable', 'date'],
            'booking_cutoff_at' => ['nullable', 'date'],
            'finalization_deadline' => ['nullable', 'date'],
        ]);

        $action->execute($church, $validated, $request->user());

        return back()->with('success', 'Mass schedule created successfully.');
    }

    public function storeSeries(Request $request, Church $church, GenerateMassSeriesOccurrencesAction $action): RedirectResponse
    {
        $this->authorize('update', $church);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'recurrence_rule' => ['required', 'array'],
            'recurrence_rule.days_of_week' => ['required', 'array', 'min:1'],
            'recurrence_rule.days_of_week.*' => ['integer', 'between:0,6'],
            'recurrence_rule.time' => ['required', 'string'],
            'recurrence_rule.duration_minutes' => ['nullable', 'integer', 'min:15'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'category_id' => ['nullable', 'exists:church_categories,id'],
            'language' => ['nullable', 'string', 'max:50'],
            'location_label' => ['nullable', 'string', 'max:100'],
            'resource_key' => ['nullable', 'string', 'max:100'],
            'booking_opens_days_before' => ['nullable', 'integer', 'min:0'],
            'booking_cutoff_hours_before' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $action->execute($church, $validated, $request->user());

        return back()->with('success', "Generated recurring Mass series with {$result['occurrences_count']} occurrences.");
    }

    public function update(Request $request, Church $church, MassSchedule $mass, UpdateMassOccurrenceAction $action): RedirectResponse
    {
        if ($mass->church_id !== $church->id) {
            abort(404);
        }

        $this->authorize('update', $mass);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:150'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['nullable', 'date'],
            'category_id' => ['nullable', 'exists:church_categories,id'],
            'language' => ['sometimes', 'string', 'max:50'],
            'location_label' => ['nullable', 'string', 'max:100'],
            'resource_key' => ['nullable', 'string', 'max:100'],
            'booking_opens_at' => ['nullable', 'date'],
            'booking_cutoff_at' => ['nullable', 'date'],
        ]);

        $action->execute($mass, $validated, $request->user());

        return back()->with('success', 'Mass occurrence updated.');
    }

    public function cancel(Request $request, Church $church, MassSchedule $mass, CancelMassScheduleAction $action): RedirectResponse
    {
        if ($mass->church_id !== $church->id) {
            abort(404);
        }

        $this->authorize('cancel', $mass);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->execute($mass, $request->user(), $validated['reason']);

        return back()->with('success', 'Mass has been cancelled.');
    }

    public function closeEarly(Request $request, Church $church, MassSchedule $mass, CloseMassBookingEarlyAction $action): RedirectResponse
    {
        if ($mass->church_id !== $church->id) {
            abort(404);
        }

        $this->authorize('closeEarly', $mass);

        $action->execute($mass, $request->user(), $request->input('reason'));

        return back()->with('success', 'Mass intention submissions closed early.');
    }

    public function extendCutoff(Request $request, Church $church, MassSchedule $mass, ExtendMassCutoffAction $action): RedirectResponse
    {
        if ($mass->church_id !== $church->id) {
            abort(404);
        }

        $this->authorize('extendCutoff', $mass);

        $validated = $request->validate([
            'booking_cutoff_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $action->execute($mass, $validated['booking_cutoff_at'], $request->user(), $validated['reason'] ?? null);

        return back()->with('success', 'Booking cutoff deadline extended.');
    }
}
