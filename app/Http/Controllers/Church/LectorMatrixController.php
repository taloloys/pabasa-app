<?php

namespace App\Http\Controllers\Church;

use App\Actions\Masses\AssignLectorAction;
use App\Actions\Masses\RemoveLectorAssignmentAction;
use App\Enums\MassStatus;
use App\Enums\MembershipRole;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\MassAssignment;
use App\Models\MassSchedule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LectorMatrixController extends Controller
{
    public function index(Request $request, Church $church): Response
    {
        $this->authorize('update', $church);

        $upcomingMasses = MassSchedule::query()
            ->with(['activeAssignments.user'])
            ->where('church_id', $church->id)
            ->where('starts_at', '>=', now()->subHours(2))
            ->where('status', '!=', MassStatus::Cancelled->value)
            ->orderBy('starts_at')
            ->get();

        $lectors = User::query()
            ->whereHas('churchMemberships', function ($q) use ($church) {
                $q->active()->where('church_id', $church->id)
                    ->whereIn('role', [MembershipRole::Lector->value, MembershipRole::ChurchAdmin->value]);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('Church/Lectors/Matrix', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'timezone' => $church->timezone,
            ],
            'upcomingMasses' => $upcomingMasses,
            'lectors' => $lectors,
        ]);
    }

    public function assign(Request $request, Church $church, MassSchedule $mass, AssignLectorAction $action): RedirectResponse
    {
        if ($mass->church_id !== $church->id) {
            abort(404);
        }

        $this->authorize('assignLector', $mass);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $lector = User::findOrFail($validated['user_id']);
        $action->execute($mass, $lector, $request->user(), $validated['notes'] ?? null);

        return back()->with('success', "Lector {$lector->name} assigned to Mass.");
    }

    public function remove(Request $request, Church $church, MassAssignment $assignment, RemoveLectorAssignmentAction $action): RedirectResponse
    {
        if ($assignment->church_id !== $church->id) {
            abort(404);
        }

        $this->authorize('update', $church);

        $action->execute($assignment, $request->user(), $request->input('reason'));

        return back()->with('success', 'Lector assignment removed.');
    }
}
