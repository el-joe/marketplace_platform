<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TravelPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelPackageController extends Controller
{
    // ── Index (review queue) ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = TravelPackage::query()
            ->with('agency')
            ->when($request->input('status'), fn($q, $v) => $q->where('status', $v))
            ->when($request->input('country'), fn($q, $v) => $q->where('destination_country', $v))
            ->when($request->input('q'), fn($q, $v) => $q->where(function ($qb) use ($v) {
                $qb->where('title_en', 'like', "%{$v}%")
                   ->orWhere('title_ar', 'like', "%{$v}%");
            }))
            ->latest();

        $packages     = $query->paginate(24)->withQueryString();
        $pendingCount = TravelPackage::where('status', 'pending_review')->count();

        return view('admin.travel-packages.index', compact('packages', 'pendingCount'));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function show(TravelPackage $travelPackage)
    {
        $travelPackage->load(['agency', 'media', 'approvedByAdmin']);

        return view('admin.travel-packages.show', compact('travelPackage'));
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(TravelPackage $travelPackage): JsonResponse
    {
        $travelPackage->update([
            'status'               => 'active',
            'approved_by_admin_id' => auth('admin')->id(),
            'approved_at'          => now(),
        ]);

        return response()->json(['message' => 'Package approved and is now live.']);
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function reject(Request $request, TravelPackage $travelPackage): JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        $travelPackage->update(['status' => 'draft']);

        return response()->json(['message' => 'Package returned to agency as draft.']);
    }

    // ── Expire ────────────────────────────────────────────────────────────────

    public function expire(TravelPackage $travelPackage): JsonResponse
    {
        $travelPackage->update(['status' => 'expired']);

        return response()->json(['message' => 'Package marked as expired.']);
    }
}
