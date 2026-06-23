<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\Category;
use App\Models\VendorListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdsController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();

        return view('partner.ads.index', compact('vendor'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $vendor   = Auth::guard('vendor')->user();
        $vendorId = $vendor->vendor_id;

        $query = AdCampaign::where('vendor_id', $vendorId)->orderByDesc('created_at');

        if ($search = $request->input('search.value')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $campaigns = $query
            ->skip($request->input('start', 0))
            ->take($request->input('length', 15))
            ->get();

        $rows = $campaigns->map(fn(AdCampaign $c) => [
            'DT_RowId' => 'tr-' . $c->id,
            'name'     => '<a href="' . route('partner.ads.show', $c->id) . '" class="font-medium text-primary-600 hover:underline">' . e($c->name) . '</a>',
            'type'     => strtoupper($c->type),
            'status'   => $this->statusBadge($c->status),
            'budget'   => number_format($c->budget_total, 2) . ' / ' . number_format($c->budget_daily ?? 0, 2) . ' SAR',
            'bid'      => number_format($c->bid, 2) . ' SAR',
            'date'     => $c->created_at->format('d M Y'),
            'actions'  => '<a href="' . route('partner.ads.show', $c->id) . '" class="inline-flex items-center px-3 py-1.5 text-xs font-medium border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">View</a>',
        ]);

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $rows,
        ]);
    }

    private function statusBadge(string $status): string
    {
        $classes = match ($status) {
            'active'           => 'bg-green-100 text-green-700',
            'paused'           => 'bg-yellow-100 text-yellow-700',
            'pending_review'   => 'bg-blue-100 text-blue-700',
            'budget_exhausted' => 'bg-orange-100 text-orange-700',
            'ended'            => 'bg-gray-100 text-gray-500',
            'rejected'         => 'bg-red-100 text-red-600',
            default            => 'bg-gray-100 text-gray-600',
        };

        return '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $classes . '">'
            . ucfirst(str_replace('_', ' ', $status)) . '</span>';
    }

    public function show(string $id)
    {
        $vendor = Auth::guard('vendor')->user();

        $campaign = AdCampaign::where('vendor_id', $vendor->vendor_id)
            ->findOrFail($id);

        return view('partner.ads.show', compact('vendor', 'campaign'));
    }
}
