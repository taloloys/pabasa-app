<?php

use App\Http\Controllers\Admin\StaffDashboardController;
use App\Http\Controllers\Church\ChurchDashboardController;
use App\Http\Controllers\Church\InvitationController;
use App\Http\Controllers\Church\MembershipController;
use App\Http\Controllers\Lector\LectorDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\OrderTrackingController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
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

// Invitations (Public show & Authenticated accept)
Route::get('/invitations/{token}', [InvitationController::class, 'showAccept'])->name('invitations.show');
Route::post('/invitations/accept', [InvitationController::class, 'accept'])
    ->middleware(['auth', 'throttle:invitation-accept'])
    ->name('invitations.accept');

// Central Admin Staff Routes
Route::middleware(['auth', 'ensure.staff:central_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
});

// Church Staff Routes
Route::middleware(['auth', 'ensure.staff:church_admin'])->prefix('churches/{church}')->name('church.')->group(function () {
    Route::get('/dashboard', [ChurchDashboardController::class, 'index'])->name('dashboard');

    // Memberships & Staff Invitations
    Route::get('/memberships', [MembershipController::class, 'index'])->name('memberships.index');
    Route::delete('/memberships/{membership}', [MembershipController::class, 'destroy'])->name('memberships.destroy');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/{invitation}', [InvitationController::class, 'revoke'])->name('invitations.revoke');
});

// Lector Staff Routes
Route::middleware(['auth', 'ensure.staff:lector'])->prefix('churches/{church}/lector')->name('lector.')->group(function () {
    Route::get('/dashboard', [LectorDashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
