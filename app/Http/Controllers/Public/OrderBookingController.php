<?php

namespace App\Http\Controllers\Public;

use App\Actions\Orders\CancelUnpaidOrderAction;
use App\Actions\Orders\CreateOrderAction;
use App\Actions\Payments\SubmitPaymentEvidenceAction;
use App\Enums\CategoryType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\ChurchCategory;
use App\Models\MassSchedule;
use App\Models\Order;
use App\Models\PricingConfiguration;
use App\Services\GuestAccess\GuestAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderBookingController extends Controller
{
    /**
     * Show the multi-step booking wizard.
     */
    public function create(Request $request, Church $church, MassSchedule $mass): Response
    {
        if ($mass->church_id !== $church->id || ! $church->isPublished()) {
            abort(404);
        }

        $categories = ChurchCategory::query()
            ->active()
            ->where('type', CategoryType::Intention->value)
            ->where(fn ($q) => $q->where('church_id', $church->id)->orWhere('is_system', true))
            ->orderBy('sort_order')
            ->get();

        $pricingConfig = PricingConfiguration::approved()
            ->forChurch($church->id)
            ->latest('version')
            ->first();

        return Inertia::render('Public/Orders/BookingWizard', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'slug' => $church->slug,
                'timezone' => $church->timezone,
                'contact_phone' => $church->contact_phone,
            ],
            'mass' => [
                'id' => $mass->id,
                'title' => $mass->title,
                'starts_at' => $mass->starts_at->toIso8601String(),
                'starts_at_formatted' => $mass->formatInChurchTimezone('starts_at', 'M d, Y h:i A'),
                'booking_cutoff_at' => $mass->booking_cutoff_at->toIso8601String(),
                'booking_cutoff_formatted' => $mass->formatInChurchTimezone('booking_cutoff_at', 'M d, Y h:i A'),
                'language' => $mass->language,
                'location_label' => $mass->location_label,
                'is_open' => $mass->isBookingOpen(),
            ],
            'categories' => $categories,
            'pricingConfig' => $pricingConfig ? [
                'id' => $pricingConfig->id,
                'currency' => $pricingConfig->currency,
                'price_per_intention' => $pricingConfig->pricing_rules['base_price_per_intention'] ?? null,
                'category_prices' => $pricingConfig->pricing_rules['category_prices'] ?? [],
                'service_fee' => $pricingConfig->pricing_rules['service_fee'] ?? null,
            ] : null,
            'user' => $request->user() ? [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ] : null,
        ]);
    }

    /**
     * Create order for a specific mass schedule.
     */
    public function storeForMass(Request $request, MassSchedule $mass, CreateOrderAction $action): RedirectResponse
    {
        $request->merge(['mass_schedule_id' => $mass->id]);

        return $this->store($request, $action);
    }

    /**
     * Create order and intentions.
     */
    public function store(Request $request, CreateOrderAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'mass_schedule_id' => ['required', 'exists:mass_schedules,id'],
            'submitter_name' => ['required_without:user', 'nullable', 'string', 'max:150'],
            'submitter_email' => ['required_without:user', 'nullable', 'email', 'max:255'],
            'privacy_acknowledged' => ['required', 'accepted'],
            'intentions' => ['required', 'array', 'min:1', 'max:50'],
            'intentions.*.category_id' => ['required', 'exists:church_categories,id'],
            'intentions.*.church_id' => ['nullable', 'integer'],
            'intentions.*.mass_schedule_id' => ['nullable', 'integer'],
            'intentions.*.person_or_family_name' => ['required', 'string', 'max:150'],
            'intentions.*.wording' => ['required', 'string', 'max:1000'],
        ]);

        $result = $action->execute($validated, $request->user());

        $order = $result['order'];

        if ($result['guest_token']) {
            session(['guest_order_token_'.$order->order_number => $result['guest_token']]);
        }

        return redirect()->route('orders.payment', ['orderNumber' => $order->order_number])
            ->with('success', 'Your intentions have been submitted. Please complete your GCash offering to proceed.')
            ->with('warnings', $result['warnings']);
    }

    /**
     * Show GCash payment offering screen.
     */
    public function payment(Request $request, string $orderNumber): Response|RedirectResponse
    {
        $order = Order::with(['church.branding', 'massSchedule', 'intentions', 'payments'])
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        if ($order->status === OrderStatus::Cancelled) {
            return redirect()->route('churches.show', ['slug' => $order->church->slug])
                ->with('error', 'This order has already been cancelled.');
        }

        $payment = $order->latestPayment;
        $guestToken = session('guest_order_token_'.$order->order_number);

        return Inertia::render('Public/Orders/Payment', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status->value,
                'expected_amount' => $order->expected_amount,
                'currency' => $order->currency,
                'submitter_name' => $order->submitter_name,
                'submitter_email' => $order->submitter_email,
                'submitter_type' => $order->submitter_type,
                'intentions_count' => $order->intentions->count(),
                'pricing_snapshot' => $order->pricing_snapshot,
            ],
            'church' => [
                'id' => $order->church->id,
                'name' => $order->church->name,
                'contact_phone' => $order->church->contact_phone,
                'gcash_name' => $order->church->branding?->gcash_name,
                'gcash_number' => $order->church->branding?->gcash_number,
                'gcash_qr_url' => $order->church->branding?->gcash_qr_url,
            ],
            'mass' => [
                'id' => $order->massSchedule->id,
                'title' => $order->massSchedule->title,
                'starts_at_formatted' => $order->massSchedule->formatInChurchTimezone('starts_at', 'M d, Y h:i A'),
                'cutoff_formatted' => $order->massSchedule->formatInChurchTimezone('booking_cutoff_at', 'M d, Y h:i A'),
            ],
            'payment' => $payment ? [
                'id' => $payment->id,
                'status' => $payment->status->value,
                'expected_amount' => $payment->expected_amount,
                'submitted_amount' => $payment->submitted_amount,
                'submitted_reference' => $payment->submitted_reference,
            ] : null,
            'guestToken' => $guestToken,
        ]);
    }

    /**
     * Submit GCash payment evidence.
     */
    public function submitEvidence(Request $request, Order $order, SubmitPaymentEvidenceAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'reference_number' => ['required', 'string', 'max:50'],
            'submitted_amount' => ['required', 'numeric', 'min:1'],
            'evidence_file' => ['required', 'file', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $payment = $order->latestPayment;
        if (! $payment) {
            abort(404, 'Payment record not found.');
        }

        $action->execute($payment, $validated, $request->user());

        return back()->with('success', 'Payment screenshot uploaded successfully. Your offering is now queued for verification.');
    }

    /**
     * Cancel an unverified unpaid order from the payment offering screen.
     */
    public function cancel(Request $request, string $orderNumber, CancelUnpaidOrderAction $action, GuestAccessService $guestAccess): RedirectResponse
    {
        $order = Order::with('church')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        // Validate submitter authorization
        if ($order->submitter_type === 'registered_user') {
            if (! $request->user() || (int) $request->user()->id !== (int) $order->user_id) {
                abort(403, 'Unauthorized.');
            }
        } else {
            $sessionToken = session('guest_order_token_'.$order->order_number);
            $inputToken = $request->input('token');
            $rawToken = $inputToken ?: $sessionToken;

            $isAuthorized = false;
            if ($rawToken) {
                $access = $guestAccess->verifyToken($rawToken);
                if ($access && (int) $access->order_id === (int) $order->id) {
                    $isAuthorized = true;
                }
            }

            if (! $isAuthorized) {
                abort(403, 'Unauthorized guest access.');
            }
        }

        if ($order->status !== OrderStatus::AwaitingPayment) {
            return back()->with('error', 'Only orders awaiting payment can be directly cancelled.');
        }

        $reason = $request->input('reason', 'Cancelled by customer before payment.');
        $action->execute($order, $request->user(), $reason);

        session()->forget('guest_order_token_'.$order->order_number);

        return redirect()->route('churches.show', ['slug' => $order->church->slug])
            ->with('success', "Your intention order {$order->order_number} was cancelled. No payment was charged.");
    }
}
