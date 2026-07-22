<?php

namespace App\Http\Controllers\Partner;

use App\Enums\ReturnRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Notifications\Customer\ReturnApprovedNotification;
use App\Notifications\Customer\ReturnRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();

        $query = ReturnRequest::where('vendor_id', $vendor->vendor_id)
            ->with(['order:id,order_number', 'subOrder:id,sub_order_number', 'customer:id,first_name,last_name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $returns = $query->paginate(20)->withQueryString();

        return view('partner.returns.index', compact('vendor', 'returns'));
    }

    public function show(string $returnNumber)
    {
        $vendor = Auth::guard('vendor')->user();

        $return = ReturnRequest::where('return_number', $returnNumber)
            ->where('vendor_id', $vendor->vendor_id)
            ->with([
                'order:id,order_number',
                'subOrder:id,sub_order_number',
                'customer:id,first_name,last_name',
                'items.orderItem:id,product_snapshot',
                'messages' => fn ($q) => $q->where('is_internal_note', false)->oldest()->with('attachments'),
            ])
            ->firstOrFail();

        return view('partner.returns.show', compact('vendor', 'return'));
    }

    public function approve(Request $request, string $returnNumber): RedirectResponse
    {
        $vendor = Auth::guard('vendor')->user();

        $returnRequest = ReturnRequest::where('return_number', $returnNumber)
            ->where('vendor_id', $vendor->vendor_id)
            ->firstOrFail();

        if ($returnRequest->status !== ReturnRequestStatus::Requested) {
            return back()->withErrors(['status' => 'This return can no longer be reviewed.']);
        }

        DB::transaction(function () use ($returnRequest) {
            $returnRequest->update(['status' => ReturnRequestStatus::Approved]);
            Notification::send($returnRequest->customer, new ReturnApprovedNotification($returnRequest));
        });

        return back()->with('success', 'Return approved. Pickup will be arranged.');
    }

    public function reject(Request $request, string $returnNumber): RedirectResponse
    {
        $vendor = Auth::guard('vendor')->user();

        $returnRequest = ReturnRequest::where('return_number', $returnNumber)
            ->where('vendor_id', $vendor->vendor_id)
            ->firstOrFail();

        $request->validate(['rejection_reason' => 'required|string|max:500']);

        if ($returnRequest->status !== ReturnRequestStatus::Requested) {
            return back()->withErrors(['status' => 'This return can no longer be reviewed.']);
        }

        DB::transaction(function () use ($returnRequest, $request) {
            $returnRequest->update([
                'status' => ReturnRequestStatus::Rejected,
                'rejection_reason' => $request->rejection_reason,
            ]);
            Notification::send($returnRequest->customer, new ReturnRejectedNotification($returnRequest));
        });

        return back()->with('success', 'Return request rejected.');
    }
}
