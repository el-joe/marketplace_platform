<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Models\TravelPackage;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $agency = Auth::guard('travel_agency')->user();

        $counts = TravelPackage::where('travel_agency_id', $agency->id)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $recentPackages = TravelPackage::where('travel_agency_id', $agency->id)
            ->with('media')
            ->latest()
            ->limit(5)
            ->get();

        return view('travel-agency.dashboard', compact('agency', 'counts', 'recentPackages'));
    }
}
