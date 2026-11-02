<?php

namespace App\Http\Controllers\InvestorPortal;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $investor = Auth::guard('investor')->user();
        $investments = $investor->investments()->orderByDesc('id')->get();

        return view('investor.dashboard', [
            'investor' => $investor,
            'investments' => $investments,
            'summary' => [
                'applications' => $investments->count(),
                'active' => $investments->where('status', 1)->count(),
                'committed_capital' => (float) $investments->where('status', 1)->sum('amount'),
            ],
        ]);
    }
}
