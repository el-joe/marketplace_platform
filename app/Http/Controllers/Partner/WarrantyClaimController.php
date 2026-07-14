<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\WarrantyClaim;
use App\Models\WarrantyClaimMessage;
use App\Notifications\Admin\WarrantyClaimVendorRepliedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class WarrantyClaimController extends Controller
{
    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    public function index(Request $request): View
    {
        $claims = WarrantyClaim::where('vendor_id', $this->vendorId())
            ->where('listing_type', WarrantyClaim::LISTING_TYPE_VENDOR)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->with(['customer:id,name', 'product:id,name'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('partner.warranty-claims.index', compact('claims'));
    }

    public function show(WarrantyClaim $claim): View
    {
        abort_unless(
            $claim->vendor_id === $this->vendorId() && $claim->listing_type === WarrantyClaim::LISTING_TYPE_VENDOR,
            403
        );

        $claim->load([
            'messages' => fn ($q) => $q->where('is_internal_note', false)->oldest('created_at'),
            'customer:id,name',
            'product:id,name',
        ]);

        return view('partner.warranty-claims.show', compact('claim'));
    }

    public function respond(Request $request, WarrantyClaim $claim): RedirectResponse
    {
        abort_unless(
            $claim->vendor_id === $this->vendorId() && $claim->listing_type === WarrantyClaim::LISTING_TYPE_VENDOR,
            403
        );

        abort_unless(
            in_array($claim->status, [WarrantyClaim::STATUS_SUBMITTED, WarrantyClaim::STATUS_UNDER_REVIEW], true),
            403
        );

        $data = $request->validate([
            'vendor_response' => 'required|string|min:10|max:2000',
        ]);

        $vendorAdmin = Auth::guard('vendor')->user();

        $claim->update(['vendor_response' => $data['vendor_response']]);

        WarrantyClaimMessage::create([
            'warranty_claim_id' => $claim->id,
            'sender_user_id' => $vendorAdmin->id,
            'sender_role' => WarrantyClaimMessage::SENDER_ROLE_VENDOR,
            'message' => $data['vendor_response'],
            'is_internal_note' => false,
            'created_at' => now(),
        ]);

        Notification::send(
            Admin::permission('warranty_claims.manage')->get(),
            new WarrantyClaimVendorRepliedNotification($claim)
        );

        return back()->with('success', __('partner.warranty_claims.response_sent'));
    }
}
