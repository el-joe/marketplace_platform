<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketerCampaign;
use App\Services\MarketerCampaignService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class MarketerCampaignController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private MarketerCampaignService $service) {}

    public function index(Request $request)
    {
        abort_unless(auth('admin')->user()->can('marketer_campaigns.view'), 403);

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
        abort_unless(auth('admin')->user()->can('marketer_campaigns.view'), 403);
        $marketerCampaign->load([
            'vendor', 'country',
            'vendorListing.productVariant.product',
            'adminListing.productVariant.product',
            'invitations.marketer.marketerProfile',
            'tieredRules',
            'conversions.order',
            'samples.invitation.marketer',
        ]);

        $categoryId = $marketerCampaign->vendorListing?->productVariant?->product?->category_id
            ?? $marketerCampaign->adminListing?->productVariant?->product?->category_id;

        $commissionSetting = $categoryId
            ? \App\Models\MarketerCommissionCountrySetting::where('category_id', $categoryId)
                ->where('country_id', $marketerCampaign->country_id)
                ->first()
            : null;

        return view('admin.marketer_campaigns.show', compact('marketerCampaign', 'commissionSetting'));
    }

    public function approve(Request $request, MarketerCampaign $marketerCampaign)
    {
        abort_unless(auth('admin')->user()->can('marketer_campaigns.approve'), 403);

        abort_unless($marketerCampaign->status === 'pending_admin', 422,
            'هذه الحملة لا يمكن قبولها في حالتها الحالية.');

        $this->service->approveCampaign($marketerCampaign, auth()->guard('admin')->user());

        return back()->with('success', 'تم قبول الحملة وأصبحت نشطة.');
    }

    public function reject(Request $request, MarketerCampaign $marketerCampaign)
    {
        abort_unless(auth('admin')->user()->can('marketer_campaigns.reject'), 403);
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $this->service->rejectCampaign(
            $marketerCampaign,
            auth()->guard('admin')->user(),
            $request->rejection_reason
        );

        return back()->with('success', 'تم رفض الحملة.');
    }

    public function updateSampleStatus(
        Request $request,
        MarketerCampaign $marketerCampaign,
        \App\Models\MarketerCampaignSample $sample
    ) {
        abort_unless(auth('admin')->user()->can('marketer_campaigns.approve'), 403);

        abort_unless($sample->campaign_id === $marketerCampaign->id, 403);

        $request->validate([
            'status' => 'required|in:pending,dispatched,delivered,returned',
        ]);

        $data = ['status' => $request->status];

        if ($request->status === 'dispatched' && !$sample->dispatched_at) {
            $data['dispatched_at'] = now();
        }
        if ($request->status === 'delivered' && !$sample->delivered_at) {
            $data['delivered_at'] = now();
        }

        $sample->update($data);

        return back()->with('success', 'تم تحديث حالة العينة.');
    }
}
