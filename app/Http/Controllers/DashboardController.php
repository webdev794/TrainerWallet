<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $currency = $request->user()->trainerProfile()->value('currency')
            ?? config('coachpay.default_currency');

        return Inertia::render('dashboard', [
            'stats' => [
                'collected_this_month' => 0,
                'outstanding' => 0,
                'overdue_count' => 0,
                'sessions_this_week' => 0,
            ],
            'currency' => $currency,
        ]);
    }
}
