<?php

namespace App\Http\Controllers\User;

use App\Actions\Requests\CreateCancellationRequestAction;
use App\Actions\Requests\CreateCorrectionRequestAction;
use App\Actions\Requests\CreateReschedulingRequestAction;
use App\Http\Controllers\Controller;
use App\Models\Intention;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderRequestController extends Controller
{
    /**
     * Edit eligible intentions prior to verification.
     */
    public function updateIntentions(Request $request, Order $order): RedirectResponse
    {
        // Rule 25: Owner can edit eligible intentions before payment verification; edit is blocked after verification
        Gate::authorize('editIntentions', $order);

        $validated = $request->validate([
            'intentions' => ['required', 'array'],
            'intentions.*.id' => ['required', 'exists:intentions,id'],
            'intentions.*.person_or_family_name' => ['required', 'string', 'max:150'],
            'intentions.*.wording' => ['required', 'string', 'max:1000'],
        ]);

        foreach ($validated['intentions'] as $item) {
            $intention = $order->intentions()->where('id', $item['id'])->first();
            if ($intention) {
                $intention->update([
                    'person_or_family_name' => $item['person_or_family_name'],
                    'wording' => $item['wording'],
                ]);
            }
        }

        return back()->with('success', 'Intentions updated successfully.');
    }

    /**
     * Request a typo / wording correction.
     */
    public function requestCorrection(Request $request, Order $order, CreateCorrectionRequestAction $action): RedirectResponse
    {
        Gate::authorize('requestChanges', $order);

        $validated = $request->validate([
            'intention_id' => ['required', 'exists:intentions,id'],
            'person_or_family_name' => ['required', 'string', 'max:150'],
            'wording' => ['required', 'string', 'max:1000'],
        ]);

        $intention = Intention::findOrFail($validated['intention_id']);

        $action->execute($order, $intention, [
            'person_or_family_name' => $validated['person_or_family_name'],
            'wording' => $validated['wording'],
        ], $request->user());

        return back()->with('success', 'Correction request submitted for review.');
    }

    /**
     * Request order cancellation.
     */
    public function requestCancellation(Request $request, Order $order, CreateCancellationRequestAction $action): RedirectResponse
    {
        Gate::authorize('requestChanges', $order);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->execute($order, $validated['reason'], null, $request->user());

        return back()->with('success', 'Cancellation request submitted for review.');
    }

    /**
     * Request order rescheduling.
     */
    public function requestRescheduling(Request $request, Order $order, CreateReschedulingRequestAction $action): RedirectResponse
    {
        Gate::authorize('requestChanges', $order);

        $validated = $request->validate([
            'requested_mass_schedule_id' => ['required', 'exists:mass_schedules,id'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->execute($order, (int) $validated['requested_mass_schedule_id'], $validated['reason'], null, $request->user());

        return back()->with('success', 'Rescheduling request submitted for review.');
    }
}
