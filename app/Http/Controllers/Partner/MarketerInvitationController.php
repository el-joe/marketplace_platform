<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\MarketerCampaignInvitation;
use App\Services\MarketerCampaignService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketerInvitationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private MarketerCampaignService $service)
    {
    }

    private function vendor(): \App\Models\Vendor
    {
        $vendor = Auth::guard('vendor')->user()->vendor;
        abort_unless($vendor->isMarketer(), 403);

        return $vendor;
    }

    public function index()
    {
        $this->authorize('marketer_invitations.view');
        $vendor = $this->vendor();

        $invitations = MarketerCampaignInvitation::where('marketer_vendor_id', $vendor->id)
            ->with(['campaign.vendor', 'campaign.vendorListing.productVariant.product', 'campaign.country'])
            ->latest()
            ->paginate(20);

        return view('partner.marketer.invitations', compact('invitations'));
    }

    public function accept(Request $request, MarketerCampaignInvitation $invitation)
    {
        $this->authorize('marketer_invitations.respond');
        $vendor = $this->vendor();
        abort_unless($invitation->marketer_vendor_id === $vendor->id, 403);

        $this->service->acceptInvitation($invitation, $request->input('note'));

        return back()->with('success', 'تم قبول دعوة الحملة.');
    }

    public function reject(Request $request, MarketerCampaignInvitation $invitation)
    {
        $this->authorize('marketer_invitations.respond');
        $vendor = $this->vendor();
        abort_unless($invitation->marketer_vendor_id === $vendor->id, 403);

        $this->service->rejectInvitation($invitation, $request->input('reason'));

        return back()->with('success', 'تم رفض دعوة الحملة.');
    }
}
