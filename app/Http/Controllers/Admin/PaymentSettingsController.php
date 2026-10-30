<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Churches\UpdateChurchGcashAction;
use App\Actions\Pricing\ApprovePricingConfigAction;
use App\Actions\Pricing\CreatePricingConfigAction;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\PricingConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingsController extends Controller
{
    /**
     * Display the Central Admin payment settings page (fee configurations + parish GCash profiles).
     */
    public function index(Request $request): Response
    {
        $configs = PricingConfiguration::with(['approvedBy', 'church'])
            ->orderByDesc('version')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'scope' => $c->scope,
                'church_id' => $c->church_id,
                'church_name' => $c->church?->name,
                'status' => $c->status->value,
                'version' => $c->version,
                'currency' => $c->currency,
                'effective_from' => $c->effective_from?->toDateTimeString(),
                'effective_until' => $c->effective_until?->toDateTimeString(),
                'pricing_rules' => $c->pricing_rules,
                'change_reason' => $c->change_reason,
                'approved_at' => $c->approved_at?->toDateTimeString(),
                'approved_by' => $c->approvedBy?->name,
                'created_at' => $c->created_at->toDateTimeString(),
            ]);

        $churches = Church::with('branding')
            ->whereIn('status', ['published', 'verified'])
            ->orderBy('name')
            ->get()
            ->map(fn ($ch) => [
                'id' => $ch->id,
                'name' => $ch->name,
                'city' => $ch->city,
                'province' => $ch->province,
                'status' => $ch->status->value,
                'gcash_name' => $ch->branding?->gcash_name,
                'gcash_number' => $ch->branding?->gcash_number,
                'gcash_qr_url' => $ch->branding?->gcash_qr_url,
                'has_qr' => (bool) $ch->branding?->gcash_qr_path,
            ]);

        $activeConfig = PricingConfiguration::approved()
            ->where('scope', 'global')
            ->latest('version')
            ->first()
            ?? PricingConfiguration::approved()->latest('version')->first();

        return Inertia::render('Admin/PaymentSettings/Index', [
            'configs' => $configs,
            'churches' => $churches,
            'activeConfig' => $activeConfig ? [
                'id' => $activeConfig->id,
                'name' => $activeConfig->name,
                'version' => $activeConfig->version,
                'currency' => $activeConfig->currency,
                'pricing_rules' => $activeConfig->pricing_rules,
                'approved_at' => $activeConfig->approved_at?->toDateTimeString(),
            ] : null,
        ]);
    }

    /**
     * Create a new draft global pricing/fee configuration.
     */
    public function store(Request $request, CreatePricingConfigAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'currency' => ['required', 'string', 'size:3'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'base_price_per_intention' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'service_fee_type' => ['required', 'in:fixed_per_order,fixed_per_slip,percentage,none'],
            'service_fee_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'service_fee_label' => ['nullable', 'string', 'max:100'],
            'service_fee_active' => ['required', 'boolean'],
            'change_reason' => ['required', 'string', 'max:1000'],
        ]);

        $config = $action->execute([
            'scope' => 'global',
            'name' => $validated['name'],
            'currency' => $validated['currency'],
            'effective_from' => $validated['effective_from'] ?? null,
            'effective_until' => $validated['effective_until'] ?? null,
            'base_price_per_intention' => $validated['base_price_per_intention'] ?? null,
            'service_fee_type' => $validated['service_fee_type'],
            'service_fee_amount' => $validated['service_fee_amount'] ?? '0.00',
            'service_fee_label' => $validated['service_fee_label'] ?? 'Platform Convenience Fee',
            'service_fee_active' => (bool) $validated['service_fee_active'],
            'change_reason' => $validated['change_reason'],
        ], $request->user());

        return redirect()->route('admin.payment-settings.index')
            ->with('success', 'Pricing configuration v'.$config->version.' created as draft.');
    }

    /**
     * Approve a draft pricing configuration.
     */
    public function approve(
        Request $request,
        PricingConfiguration $config,
        ApprovePricingConfigAction $action
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $action->execute($config, $request->user(), $validated['reason']);

        return redirect()->route('admin.payment-settings.index')
            ->with('success', 'Pricing configuration v'.$config->version.' approved and is now active.');
    }

    /**
     * Update a parish's GCash credentials (QR image, account name, mobile number).
     */
    public function updateChurchGcash(
        Request $request,
        Church $church,
        UpdateChurchGcashAction $action
    ): RedirectResponse {
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

        return back()->with('success', 'GCash credentials for '.$church->name.' updated successfully.');
    }
}
