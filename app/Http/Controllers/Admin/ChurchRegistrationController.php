<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Churches\CreateChurchAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChurchRegistrationController extends Controller
{
    /**
     * Canonically register a new parish.
     */
    public function store(Request $request, CreateChurchAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:churches,slug'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'timezone' => ['required', 'string', 'timezone'],
            'auto_verify' => ['nullable', 'boolean'],
            'initial_admin_email' => ['nullable', 'email', 'max:255'],
        ]);

        $result = $action->execute($validated, $request->user());
        $church = $result['church'];

        $message = "Parish '{$church->name}' has been successfully registered.";
        if (! empty($result['invitation'])) {
            $message .= " An administrator invitation was dispatched to {$validated['initial_admin_email']}.";
        }

        return back()->with('success', $message);
    }
}
