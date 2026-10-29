<?php

namespace App\Http\Controllers\Church;

use App\Actions\Churches\UpdateBrandingAction;
use App\Http\Controllers\Controller;
use App\Models\Church;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChurchBrandingController extends Controller
{
    public function edit(Request $request, Church $church): Response
    {
        $this->authorize('branding', $church);

        return Inertia::render('Church/Branding/Edit', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'address' => $church->address,
                'city' => $church->city,
                'province' => $church->province,
                'contact_email' => $church->contact_email,
                'contact_phone' => $church->contact_phone,
            ],
            'branding' => $church->branding ? [
                'logo_path' => $church->branding->logo_path,
                'banner_path' => $church->branding->banner_path,
                'theme_color' => $church->branding->theme_color,
                'public_description' => $church->branding->public_description,
                'contact_email' => $church->branding->contact_email,
                'contact_phone' => $church->branding->contact_phone,
            ] : null,
        ]);
    }

    public function update(Request $request, Church $church, UpdateBrandingAction $action): RedirectResponse
    {
        $this->authorize('branding', $church);

        $validated = $request->validate([
            'logo_path' => ['nullable', 'string', 'max:255'],
            'banner_path' => ['nullable', 'string', 'max:255'],
            'theme_color' => ['nullable', 'string', 'max:50'],
            'public_description' => ['nullable', 'string', 'max:5000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        $action->execute($church, $validated, $request->user());

        return back()->with('success', 'Church branding updated successfully.');
    }
}
