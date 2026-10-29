<?php

namespace App\Http\Controllers\Lector;

use App\Actions\Masses\CloseMassBookingEarlyAction;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\MassSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LectorMassController extends Controller
{
    public function index(Request $request, Church $church): Response
    {
        $user = $request->user();

        // Fetch only masses actively assigned to this lector
        $assignedMasses = MassSchedule::query()
            ->with(['category'])
            ->where('church_id', $church->id)
            ->whereHas('activeAssignments', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('assignment_role', 'lector');
            })
            ->orderBy('starts_at', 'desc')
            ->paginate(15);

        return Inertia::render('Lector/Masses/Index', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'timezone' => $church->timezone,
            ],
            'assignedMasses' => $assignedMasses,
        ]);
    }

    public function show(Request $request, Church $church, MassSchedule $mass): Response
    {
        if ($mass->church_id !== $church->id) {
            abort(404, 'Mass not found.');
        }

        // Rule 13: Lector cannot view unassigned Mass details by changing URL ID
        if (! $request->user()->can('view', $mass)) {
            abort(404, 'Mass assignment not found.');
        }

        $mass->load(['category', 'activeAssignments.user']);

        return Inertia::render('Lector/Masses/Show', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'timezone' => $church->timezone,
            ],
            'mass' => $mass,
        ]);
    }

    public function closeEarly(Request $request, Church $church, MassSchedule $mass, CloseMassBookingEarlyAction $action): RedirectResponse
    {
        if ($mass->church_id !== $church->id) {
            abort(404, 'Mass not found.');
        }

        // Rule 10: Assigned lector can close submissions early
        if (! $request->user()->can('closeEarly', $mass)) {
            abort(403, 'Unauthorized to close submissions for this Mass.');
        }

        $action->execute($mass, $request->user(), $request->input('reason', 'Closed early by assigned lector.'));

        return back()->with('success', 'Submissions closed early.');
    }
}
