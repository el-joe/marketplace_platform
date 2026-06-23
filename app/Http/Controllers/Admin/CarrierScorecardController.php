<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarrierPerformanceRating;
use App\Models\ShippingCompany;
use App\Services\CarrierClaimService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarrierScorecardController extends Controller
{
    public function __construct(private readonly CarrierClaimService $service) {}

    public function index(Request $request): View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('carrier-claims.view'), 403);

        $period = $request->input('period', 'month');

        $companies = ShippingCompany::where('status', 'active')
            ->withCount(['agents'])
            ->get();

        $scorecards = $companies->map(fn($company) => [
            'company'   => $company,
            'scorecard' => $this->service->getCarrierScorecard($company, $period),
        ])->sortByDesc(fn($row) => $row['scorecard']['avg_rating'] ?? 0)->values();

        return view('admin.carrier-scorecard.index', compact('scorecards', 'period'));
    }

    public function show(Request $request, ShippingCompany $shippingCompany): View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('carrier-claims.view'), 403);

        $period    = $request->input('period', 'month');
        $scorecard = $this->service->getCarrierScorecard($shippingCompany, $period);
        $trend     = $this->service->getRatingTrend($shippingCompany, 6);

        $recentRatings = CarrierPerformanceRating::where('shipping_company_id', $shippingCompany->id)
            ->with('subOrder')
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.carrier-scorecard.show', compact(
            'shippingCompany', 'scorecard', 'trend', 'recentRatings', 'period'
        ));
    }

    public function trendData(ShippingCompany $shippingCompany): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('carrier-claims.view'), 403);

        return response()->json($this->service->getRatingTrend($shippingCompany, 6));
    }
}
