<?php

namespace App\Http\Controllers\TravelAgencyPortal;

use App\Http\Controllers\Controller;
use App\Models\TravelBooking;
use App\Models\TravelPackage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $agency = Auth::guard('travel_agency')->user();

        $packageCounts = TravelPackage::where('travel_agency_id', $agency->id)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // Booking stats scoped to this agency's packages
        $bookingStats = TravelBooking::query()
            ->whereHas('package', fn ($q) => $q->where('travel_agency_id', $agency->id))
            ->selectRaw('COUNT(*) as total, SUM(total_price_cents) as revenue')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->first();

        $totalBookings = TravelBooking::query()
            ->whereHas('package', fn ($q) => $q->where('travel_agency_id', $agency->id))
            ->count();

        $recentPackages = TravelPackage::where('travel_agency_id', $agency->id)
            ->with('media')
            ->latest()
            ->limit(5)
            ->get();

        $recentBookings = TravelBooking::query()
            ->whereHas('package', fn ($q) => $q->where('travel_agency_id', $agency->id))
            ->with(['package', 'customer'])
            ->latest()
            ->limit(5)
            ->get();

        return view('travel-agency.dashboard', compact(
            'agency',
            'packageCounts',
            'totalBookings',
            'bookingStats',
            'recentPackages',
            'recentBookings',
        ));
    }
}
