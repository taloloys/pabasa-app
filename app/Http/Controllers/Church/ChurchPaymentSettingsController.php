<?php

namespace App\Http\Controllers\Church;

use App\Actions\Churches\UpdateChurchGcashAction;
use App\Http\Controllers\Controller;
use App\Models\Church;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChurchPaymentSettingsController extends Controller
{
    /**
     * Show the parish payment settings page (GCash credentials).
     */
    public function edit(Request $request, Church $church): Response
    {
        $this->authorize('branding', $church);

        $branding = $church->branding;

        return Inertia::render('Church/PaymentSettings/Edit', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
            ],
            'gcash' => [
                'gcash_name' => $branding?->gcash_name,
                'gcash_number' => $branding?->gcash_number,
                'gcash_qr_url' => $branding?->gcash_qr_url,
            ],
        ]);
    }

    /**
     * Update the parish's GCash credentials.
     */
    public function update(Request $request, Church $church, UpdateChurchGcashAction $action): RedirectResponse
    {
        $this->authorize('branding', $church);

        $validated = $request->validate([
            'gcash_name' => ['nullable', 'string', 'max:100'],
            'gcash_number' => ['nullable', 'string', 'max:30', 'regex:/^(\+63|0)\d{10}$/'],
            'gcash_qr_image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $action->execute($church, [
            'gcash_name' => $validated['gcash_name'] ?? null,
            'gcash_number' => $validated['gcash_number'] ?? null,
            'gcash_qr_image' => $request->file('gcash_qr_image'),
        ], $request->user());

        return back()->with('success', 'GCash payment settings updated successfully.');
    }
}
