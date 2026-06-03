<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\MarketerSampleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MarketerSampleController extends Controller
{
    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    /**
     * List sample requests targeting this vendor.
     */
    public function index(): View
    {
        $vendorId = $this->vendorId();

        $requests = MarketerSampleRequest::where('vendor_id', $vendorId)
            ->with(['marketer', 'campaign', 'items.vendorListing.product'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $stats = [
            'total' => MarketerSampleRequest::where('vendor_id', $vendorId)->count(),
            'requested' => MarketerSampleRequest::where('vendor_id', $vendorId)->where('status', 'requested')->count(),
            'approved' => MarketerSampleRequest::where('vendor_id', $vendorId)->where('status', 'approved')->count(),
            'dispatched' => MarketerSampleRequest::where('vendor_id', $vendorId)->where('status', 'dispatched')->count(),
        ];

        return view('partner.marketer-samples.index', [
            'requests' => $requests,
            'stats' => $stats,
        ]);
    }

    /**
     * Vendor approves a sample request.
     */
    public function approve(MarketerSampleRequest $req): JsonResponse
    {
        abort_if($req->vendor_id !== $this->vendorId(), 403);
        abort_unless($req->status === 'requested', 422, 'Only pending requests can be approved.');

        $req->update(['status' => 'approved', 'approved_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Sample request approved.']);
    }

    /**
     * Vendor rejects a sample request.
     */
    public function reject(MarketerSampleRequest $req): JsonResponse
    {
        abort_if($req->vendor_id !== $this->vendorId(), 403);
        abort_unless(in_array($req->status, ['requested', 'approved']), 422, 'Cannot reject at this stage.');

        $req->update(['status' => 'rejected']);

        return response()->json(['success' => true, 'message' => 'Sample request rejected.']);
    }
}
