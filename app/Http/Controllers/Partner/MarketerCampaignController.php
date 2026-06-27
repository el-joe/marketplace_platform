<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\MarketerCampaign;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MarketerCampaignController extends Controller
{
    private function vendorId(): string
    {
        return Auth::guard('vendor')->user()->vendor_id;
    }

    /**
     * List all active marketer campaigns that include products belonging to this vendor.
     */
    public function index(): View
    {
        $vendorId = $this->vendorId();

        $campaigns = MarketerCampaign::where('vendor_id', $vendorId)
            ->with(['marketer'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('partner.marketer-campaigns.index', [
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Show details of a single marketer campaign (only if vendor's products are included).
     */
    public function show(MarketerCampaign $campaign): View
    {
        $vendorId = $this->vendorId();

        abort_unless($campaign->vendor_id === $vendorId, 403);

        $campaign->load(['marketer', 'conversions']);

        return view('partner.marketer-campaigns.show', [
            'campaign' => $campaign,
        ]);
    }
}
