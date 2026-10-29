<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderTrackingController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Secure order tracking access granted.',
                'orders' => [],
            ]);
        }

        return Inertia::render('User/Orders/Tracking', [
            'orders' => [],
        ]);
    }
}
