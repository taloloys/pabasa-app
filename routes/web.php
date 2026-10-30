<?php

use App\Enums\ChurchStatus;
use App\Enums\MassStatus;
use App\Http\Controllers\Admin\ChurchRegistrationController;
use App\Http\Controllers\Admin\ChurchVerificationController;
use App\Http\Controllers\Admin\PaymentReviewController;
use App\Http\Controllers\Admin\PaymentSettingsController;
use App\Http\Controllers\Admin\StaffDashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Church\ChurchBrandingController;
use App\Http\Controllers\Church\ChurchCategoryController;
use App\Http\Controllers\Church\ChurchDashboardController;
use App\Http\Controllers\Church\ChurchPaymentSettingsController;
use App\Http\Controllers\Church\InvitationController;
use App\Http\Controllers\Church\LectorMatrixController;
use App\Http\Controllers\Church\MassScheduleController;
use App\Http\Controllers\Church\MembershipController;
use App\Http\Controllers\Guest\GuestOrderTrackingController;
use App\Http\Controllers\Lector\LectorDashboardController;
use App\Http\Controllers\Lector\LectorMassController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\ChurchDirectoryController;
use App\Http\Controllers\Public\OrderBookingController;
use App\Http\Controllers\User\OrderRequestController;
use App\Http\Controllers\User\OrderTrackingController;
use App\Models\Church;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    $publishedChurches = Church::query()
        ->where('status', ChurchStatus::Published)
        ->with([
            'branding',
            'massSchedules' => function ($query) {
                $query->where('status', MassStatus::Scheduled)
                    ->where('booking_status', 'open')
                    ->where('booking_cutoff_at', '>', now())
                    ->orderBy('starts_at')
                    ->limit(4);
            },
        ])
        ->get();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'publishedChurches' => $publishedChurches,
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Secure Order Tracking (Verified registered users only in Phase 1)
Route::get('/orders/tracking', [OrderTrackingController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('orders.tracking');

// Locale Switcher
Route::post('/locale', function (Request $request) {
    $validated = $request->validate([
        'locale' => ['required', 'string', 'in:en,fil,ceb'],
    ]);

    $locale = $validated['locale'];
    session(['locale' => $locale]);
    app()->setLocale($locale);

    if ($user = $request->user()) {
        $user->update(['preferred_locale' => $locale]);
    }

    if ($request->wantsJson()) {
        return response()->json(['locale' => $locale]);
    }

    return back();
})->name('locale.switch');

// Public Church Directory & Church Schedules
Route::get('/churches', [ChurchDirectoryController::class, 'index'])->name('churches.index');
Route::get('/churches/{slug}', [ChurchDirectoryController::class, 'show'])->name('churches.show');

// Public Intention Booking & Payment Offering
Route::get('/churches/{church}/masses/{mass}/book', [OrderBookingController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderBookingController::class, 'store'])->name('orders.store');
Route::post('/masses/{mass}/orders', [OrderBookingController::class, 'storeForMass'])->name('orders.store-for-mass');
Route::get('/orders/{orderNumber}/payment', [OrderBookingController::class, 'payment'])->name('orders.payment');
Route::post('/orders/{order}/evidence', [OrderBookingController::class, 'submitEvidence'])->name('orders.evidence');

// Guest Order Tracking & Recovery
Route::get('/guest/track/{token}', [GuestOrderTrackingController::class, 'show'])->name('guest.orders.show');
Route::post('/guest/track/{token}/challenge', [GuestOrderTrackingController::class, 'verifyChallenge'])->name('guest.orders.challenge');
Route::post('/guest/track/{token}/verify', [GuestOrderTrackingController::class, 'verifyChallenge'])->name('guest.orders.verify');
Route::post('/guest/recover', [GuestOrderTrackingController::class, 'recover'])->middleware('throttle:10,1')->name('guest.orders.recover');

// Invitations (Public show & Authenticated accept)
Route::get('/invitations/{token}', [InvitationController::class, 'showAccept'])->name('invitations.show');
Route::post('/invitations/accept', [InvitationController::class, 'accept'])
    ->middleware(['auth', 'throttle:invitation-accept'])
    ->name('invitations.accept');

// Central Admin Staff Routes
Route::middleware(['auth', 'ensure.staff:central_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
    Route::post('/churches', [ChurchRegistrationController::class, 'store'])->name('churches.store');
    Route::post('/churches/{church}/verify', [ChurchVerificationController::class, 'verify'])->name('churches.verify');
    Route::post('/churches/{church}/publish', [ChurchVerificationController::class, 'publish'])->name('churches.publish');
    Route::post('/churches/{church}/suspend', [ChurchVerificationController::class, 'suspend'])->name('churches.suspend');

    // Payment Review Queue & Workbench
    Route::get('/payments', [PaymentReviewController::class, 'index']);
    Route::get('/payments/review', [PaymentReviewController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [PaymentReviewController::class, 'show']);
    Route::get('/payments/{payment}/workbench', [PaymentReviewController::class, 'show'])->name('payments.workbench');
    Route::get('/payments/{payment}/evidence/{evidence}', [PaymentReviewController::class, 'streamEvidence'])->name('payments.evidence');
    Route::post('/payments/{payment}/verify', [PaymentReviewController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [PaymentReviewController::class, 'reject'])->name('payments.reject');

    // Payment Settings: Platform Fee Configuration & Parish GCash Management
    Route::get('/payment-settings', [PaymentSettingsController::class, 'index'])->name('payment-settings.index');
    Route::post('/payment-settings/configs', [PaymentSettingsController::class, 'store'])->name('payment-settings.configs.store');
    Route::post('/payment-settings/configs/{config}/approve', [PaymentSettingsController::class, 'approve'])->name('payment-settings.configs.approve');
    Route::post('/payment-settings/churches/{church}/gcash', [PaymentSettingsController::class, 'updateChurchGcash'])->name('payment-settings.churches.gcash');

    // User & Staff Oversight
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/roles', [UserManagementController::class, 'assignRole'])->name('users.roles.assign');
    Route::delete('/users/{user}/roles/{membership}', [UserManagementController::class, 'revokeRole'])->name('users.roles.revoke');
});

// Church Staff Routes
Route::middleware(['auth', 'ensure.staff:church_admin'])->prefix('churches/{church}')->name('church.')->group(function () {
    Route::get('/dashboard', [ChurchDashboardController::class, 'index'])->name('dashboard');

    // Branding
    Route::get('/branding', [ChurchBrandingController::class, 'edit'])->name('branding.edit');
    Route::patch('/branding', [ChurchBrandingController::class, 'update'])->name('branding.update');

    // Categories
    Route::get('/categories', [ChurchCategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [ChurchCategoryController::class, 'store'])->name('categories.store');
    Route::patch('/categories/{category}', [ChurchCategoryController::class, 'update'])->name('categories.update');
    Route::post('/categories/{category}/toggle', [ChurchCategoryController::class, 'toggleActive'])->name('categories.toggle');

    // Mass Schedules
    Route::get('/masses', [MassScheduleController::class, 'index'])->name('masses.index');
    Route::post('/masses', [MassScheduleController::class, 'store'])->name('masses.store');
    Route::post('/masses/series', [MassScheduleController::class, 'storeSeries'])->name('masses.series');
    Route::patch('/masses/{mass}', [MassScheduleController::class, 'update'])->name('masses.update');
    Route::post('/masses/{mass}/cancel', [MassScheduleController::class, 'cancel'])->name('masses.cancel');
    Route::post('/masses/{mass}/close-early', [MassScheduleController::class, 'closeEarly'])->name('masses.close-early');
    Route::post('/masses/{mass}/extend-cutoff', [MassScheduleController::class, 'extendCutoff'])->name('masses.extend-cutoff');

    // Lector Assignment Matrix
    Route::get('/lectors/matrix', [LectorMatrixController::class, 'index'])->name('lectors.matrix');
    Route::post('/masses/{mass}/assign-lector', [LectorMatrixController::class, 'assign'])->name('lectors.assign');
    Route::delete('/assignments/{assignment}', [LectorMatrixController::class, 'remove'])->name('lectors.remove');

    // Memberships & Staff Invitations
    Route::get('/memberships', [MembershipController::class, 'index'])->name('memberships.index');
    Route::delete('/memberships/{membership}', [MembershipController::class, 'destroy'])->name('memberships.destroy');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/{invitation}', [InvitationController::class, 'revoke'])->name('invitations.revoke');

    // GCash Payment Settings
    Route::get('/payment-settings', [ChurchPaymentSettingsController::class, 'edit'])->name('payment-settings.edit');
    Route::post('/payment-settings', [ChurchPaymentSettingsController::class, 'update'])->name('payment-settings.update');
});

// Lector Staff Routes
Route::middleware(['auth', 'ensure.staff:lector'])->prefix('churches/{church}/lector')->name('lector.')->group(function () {
    Route::get('/dashboard', [LectorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/masses', [LectorMassController::class, 'index'])->name('masses.index');
    Route::get('/masses/{mass}', [LectorMassController::class, 'show'])->name('masses.show');
    Route::post('/masses/{mass}/close-early', [LectorMassController::class, 'closeEarly'])->name('masses.close-early');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Order Intention Edits & Change Requests
    Route::patch('/orders/{order}/intentions', [OrderRequestController::class, 'updateIntentions'])->name('orders.intentions.update');
    Route::put('/orders/{order}/intentions', [OrderRequestController::class, 'updateIntentions']);
    Route::post('/orders/{order}/corrections', [OrderRequestController::class, 'requestCorrection'])->name('orders.corrections.store');
    Route::post('/user/orders/{order}/requests/correction', [OrderRequestController::class, 'requestCorrection']);
    Route::post('/orders/{order}/cancellations', [OrderRequestController::class, 'requestCancellation'])->name('orders.cancellations.store');
    Route::post('/user/orders/{order}/requests/cancellation', [OrderRequestController::class, 'requestCancellation']);
    Route::post('/orders/{order}/rescheduling', [OrderRequestController::class, 'requestRescheduling'])->name('orders.rescheduling.store');
    Route::post('/user/orders/{order}/requests/rescheduling', [OrderRequestController::class, 'requestRescheduling']);
});

require __DIR__.'/auth.php';
