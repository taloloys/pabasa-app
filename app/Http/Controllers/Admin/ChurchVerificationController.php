<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Churches\PublishChurchAction;
use App\Actions\Churches\SuspendChurchAction;
use App\Actions\Churches\VerifyChurchAction;
use App\Http\Controllers\Controller;
use App\Models\Church;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChurchVerificationController extends Controller
{
    public function verify(Request $request, Church $church, VerifyChurchAction $action): RedirectResponse
    {
        $action->execute($church, $request->user(), $request->input('reason'));

        return back()->with('success', "Church '{$church->name}' has been verified.");
    }

    public function publish(Request $request, Church $church, PublishChurchAction $action): RedirectResponse
    {
        $action->execute($church, $request->user(), $request->input('reason'));

        return back()->with('success', "Church '{$church->name}' is now published.");
    }

    public function suspend(Request $request, Church $church, SuspendChurchAction $action): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->execute($church, $request->user(), $request->input('reason'));

        return back()->with('success', "Church '{$church->name}' has been suspended.");
    }
}
