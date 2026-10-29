<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $user = $request->user();

        $orders = Order::query()
            ->with([
                'church:id,name,slug,contact_phone,contact_email',
                'massSchedule:id,title,starts_at,language,location_label',
                'intentions.category',
                'latestPayment.latestEvidence',
            ])
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere(function ($q) use ($user) {
                        $q->whereNull('user_id')
                            ->where('submitter_email', $user->email);
                    });
            })
            ->latest('submitted_at')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Secure order tracking access granted.',
                'orders' => $orders,
            ]);
        }

        return Inertia::render('User/Orders/Tracking', [
            'orders' => $orders,
        ]);
    }
}
