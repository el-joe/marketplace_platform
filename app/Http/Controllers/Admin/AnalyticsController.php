<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsService $analytics)
    {
    }

    // ── Page ──────────────────────────────────────────────────────────────────

    public function index(): \Illuminate\View\View
    {
        $countries = Country::where('is_active', true)
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'code']);

        return view('admin.analytics.index', compact('countries'));
    }

    // ── AJAX endpoints ────────────────────────────────────────────────────────

    public function overview(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->overview($request)]);
    }

    public function revenueChart(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->revenueChart($request)]);
    }

    public function ordersByStatus(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->ordersByStatus($request)]);
    }

    public function ordersByPaymentMethod(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->ordersByPaymentMethod($request)]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->topProducts($request)]);
    }

    public function topVendors(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->topVendors($request)]);
    }

    public function topCategories(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->topCategories($request)]);
    }

    public function customerStats(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->customerStats($request)]);
    }

    public function searchAnalytics(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->searchAnalytics($request)]);
    }

    public function productAnalytics(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->productAnalytics($request)]);
    }

    public function slaMetrics(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->slaMetrics($request)]);
    }

    public function adPerformance(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->adPerformance($request)]);
    }

    public function flashSaleAnalytics(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->flashSaleAnalytics($request)]);
    }

    public function returnAnalytics(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->returnAnalytics($request)]);
    }

    public function supportMetrics(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->analytics->supportMetrics($request)]);
    }
}
