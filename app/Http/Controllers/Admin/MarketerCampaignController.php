<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{MarketerCampaign, Vendor};
use App\Services\MarketerCampaignService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class MarketerCampaignController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private MarketerCampaignService $service) {}

    public function index(Request $request)
    {
        $this->authorize('marketer_campaigns.view');

        $campaigns = MarketerCampaign::with([
            'vendor', 'country',
            'vendorListing.productVariant.product',
            'adminListing.productVariant.product',
            'invitations.marketer',
        ])
        ->when($request->status, fn($q) => $q->where('status', $request->status))
        ->when($request->country_id, fn($q) => $q->where('country_id', $request->country_id))
        ->latest()
        ->paginate(20);

        $pendingCount = MarketerCampaign::where('status', 'pending_admin')->count();
        $countries = \App\Models\Country::orderBy('name_en')->get();

        return view('admin.marketer_campaigns.index', compact('campaigns', 'pendingCount', 'countries'));
    }

    public function show(MarketerCampaign $marketerCampaign)
    {
        $this->authorize('marketer_campaigns.view');
        $marketerCampaign->load([
            'vendor', 'country',
            'vendorListing.productVariant.product',
            'adminListing.productVariant.product',
            'invitations.marketer.marketerProfile',
            'tieredRules',
            'conversions.order',
            'samples',
        ]);

        $availableMarketers = Vendor::whereNotNull('marketer_type')
            ->where('global_status', 'active')
            ->where('country_id', $marketerCampaign->country_id)
            ->withCount(['campaignInvitations as accepted_campaigns' => fn($q) => $q->where('status','accepted')])
            ->get();

        return view('admin.marketer_campaigns.show', compact('marketerCampaign', 'availableMarketers'));
    }

    public function approve(Request $request, MarketerCampaign $marketerCampaign)
    {
        $this->authorize('marketer_campaigns.approve');

        $request->validate([
            'platform_commission_amount' => 'required|integer|min:0',
            'marketer_commission_amount' => 'required|integer|min:0',
            'marketer_vendor_ids'        => 'required|array|min:1',
            'marketer_vendor_ids.*'      => 'uuid|exists:vendors,id',
        ]);

        $this->service->approveCampaign(
            $marketerCampaign,
            $request->integer('platform_commission_amount'),
            $request->integer('marketer_commission_amount'),
            auth()->guard('admin')->user(),
            $request->marketer_vendor_ids
        );

        return back()->with('success', 'تم قبول الحملة وإرسال الدعوات للماركترز.');
    }

    public function reject(Request $request, MarketerCampaign $marketerCampaign)
    {
        $this->authorize('marketer_campaigns.reject');
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $this->service->rejectCampaign(
            $marketerCampaign,
            auth()->guard('admin')->user(),
            $request->rejection_reason
        );

        return back()->with('success', 'تم رفض الحملة.');
    }
}
