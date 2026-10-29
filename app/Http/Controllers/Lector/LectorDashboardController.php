<?php

namespace App\Http\Controllers\Lector;

use App\Http\Controllers\Controller;
use App\Models\Church;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LectorDashboardController extends Controller
{
    public function index(Request $request, Church $church): Response
    {
        return Inertia::render('Lector/Dashboard', [
            'church' => $church,
        ]);
    }
}
