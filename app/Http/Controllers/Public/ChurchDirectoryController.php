<?php

namespace App\Http\Controllers\Public;

use App\Enums\ChurchStatus;
use App\Enums\MassStatus;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\MassSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ChurchDirectoryController extends Controller
{
    /**
     * Display public directory of published churches.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $city = $request->input('city');
        $province = $request->input('province');

        $query = Church::query()
            ->with(['branding'])
            ->withCount(['massSchedules as upcoming_masses_count' => function ($q) {
                $q->where('starts_at', '>=', now())
                    ->where('status', '!=', MassStatus::Cancelled->value);
            }])
            ->where('status', ChurchStatus::Published->value);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('province', 'like', "%{$search}%");
            });
        }

        if ($city) {
            $query->where('city', $city);
        }

        if ($province) {
            $query->where('province', $province);
        }

        $churches = $query->orderBy('name')->paginate(12)->withQueryString();

        $availableCities = Cache::remember('directory:published_cities', 300, function () {
            return Church::where('status', ChurchStatus::Published->value)
                ->whereNotNull('city')
                ->distinct()
                ->pluck('city')
                ->sort()
                ->values();
        });

        $availableProvinces = Cache::remember('directory:published_provinces', 300, function () {
            return Church::where('status', ChurchStatus::Published->value)
                ->whereNotNull('province')
                ->distinct()
                ->pluck('province')
                ->sort()
                ->values();
        });

        return Inertia::render('Public/Churches/Index', [
            'churches' => $churches,
            'filters' => [
                'search' => $search,
                'city' => $city,
                'province' => $province,
            ],
            'availableCities' => $availableCities,
            'availableProvinces' => $availableProvinces,
        ]);
    }

    /**
     * Display published church profile and mass schedule.
     */
    public function show(string $slug): Response
    {
        $church = Church::query()
            ->with(['branding'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Rule 1 & 2: Non-published churches return 404 on public routes
        if (! $church->isPublished()) {
            abort(404, 'Church not found or currently not published.');
        }

        $upcomingMasses = MassSchedule::query()
            ->with(['category'])
            ->where('church_id', $church->id)
            ->where('starts_at', '>=', now())
            ->where('status', '!=', MassStatus::Cancelled->value)
            ->orderBy('starts_at', 'asc')
            ->get()
            ->map(function (MassSchedule $mass) use ($church) {
                $tz = $church->timezone ?: 'Asia/Manila';

                return [
                    'id' => $mass->id,
                    'title' => $mass->title,
                    'starts_at' => $mass->starts_at->toIso8601String(),
                    'starts_at_formatted' => $mass->starts_at->copy()->setTimezone($tz)->format('M d, Y h:i A'),
                    'ends_at_formatted' => $mass->ends_at ? $mass->ends_at->copy()->setTimezone($tz)->format('h:i A') : null,
                    'booking_cutoff_at' => $mass->booking_cutoff_at->toIso8601String(),
                    'booking_cutoff_formatted' => $mass->booking_cutoff_at->copy()->setTimezone($tz)->format('M d, Y h:i A'),
                    'language' => $mass->language,
                    'location_label' => $mass->location_label,
                    'booking_status' => $mass->booking_status->value,
                    'is_booking_open' => $mass->isBookingOpen(),
                    'is_cutoff_passed' => $mass->isCutoffPassed(),
                    'category' => $mass->category ? [
                        'id' => $mass->category->id,
                        'label' => $mass->category->label,
                        'color' => $mass->category->color,
                    ] : null,
                ];
            });

        return Inertia::render('Public/Churches/Show', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'slug' => $church->slug,
                'address' => $church->address,
                'city' => $church->city,
                'province' => $church->province,
                'contact_email' => $church->contact_email,
                'contact_phone' => $church->contact_phone,
                'timezone' => $church->timezone,
                'branding' => $church->branding ? [
                    'logo_path' => $church->branding->logo_path,
                    'banner_path' => $church->branding->banner_path,
                    'theme_color' => $church->branding->theme_color,
                    'public_description' => $church->branding->public_description,
                ] : null,
            ],
            'upcomingMasses' => $upcomingMasses,
        ]);
    }
}
