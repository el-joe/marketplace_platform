<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
