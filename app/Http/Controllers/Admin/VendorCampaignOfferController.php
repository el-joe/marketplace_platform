<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorCampaignInvitationStatus;
use App\Enums\VendorCampaignOfferStatus;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Vendor;
use App\Models\VendorCampaignOffer;
use App\Notifications\Marketer\VendorCampaignInvitationReceived;
use App\Notifications\Vendor\VendorCampaignOfferApproved;
use App\Notifications\Vendor\VendorCampaignOfferRejected;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class VendorCampaignOfferController extends Controller
{
    use HasDataTable;

    public function index(): View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('campaign_offers.view'), 403);

        $stats = [
            'pending' => VendorCampaignOffer::where('status', VendorCampaignOfferStatus::PendingAdmin->value)->count(),
            'active'  => VendorCampaignOffer::where('status', VendorCampaignOfferStatus::Active->value)->count(),
            'draft'   => VendorCampaignOffer::where('status', VendorCampaignOfferStatus::Draft->value)->count(),
            'ended'   => VendorCampaignOffer::where('status', VendorCampaignOfferStatus::Ended->value)->count(),
        ];

        $vendors = Vendor::orderBy('store_name')->get(['id', 'store_name']);

        return view('admin.vendor-campaign-offers.index', compact('stats', 'vendors'));
    }

    public function datatable(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('campaign_offers.view'), 403);

        $query = VendorCampaignOffer::query()
            ->select('vendor_campaign_offers.*')
            ->with(['vendor'])
            ->withCount('invitations')
            ->join('vendors', 'vendors.id', '=', 'vendor_campaign_offers.vendor_id')
            ->orderByRaw("FIELD(vendor_campaign_offers.status, 'pending_admin') DESC")
            ->orderByDesc('vendor_campaign_offers.created_at');

        $query = $this->applyFilters($query, $request, [
            'status'    => fn($q, $v) => $q->where('vendor_campaign_offers.status', $v),
            'vendor_id' => fn($q, $v) => $q->where('vendor_campaign_offers.vendor_id', $v),
            'date_from' => fn($q, $v) => $q->whereDate('vendor_campaign_offers.starts_at', '>=', $v),
            'date_to'   => fn($q, $v) => $q->whereDate('vendor_campaign_offers.ends_at', '<=', $v),
        ]);

        $columns = [
            ['searchable_columns' => ['vendors.store_name'], 'orderable_column' => 'vendors.store_name'],
            ['searchable_columns' => ['vendor_campaign_offers.name'], 'orderable_column' => 'vendor_campaign_offers.name'],
            ['searchable_columns' => [], 'orderable_column' => 'vendor_campaign_offers.campaign_type'],
            ['searchable_columns' => [], 'orderable_column' => 'vendor_campaign_offers.offered_commission_rate'],
            ['searchable_columns' => [], 'orderable_column' => null], // products count
            ['searchable_columns' => [], 'orderable_column' => 'vendor_campaign_offers.starts_at'],
            ['searchable_columns' => [], 'orderable_column' => null], // invited count
            ['searchable_columns' => [], 'orderable_column' => 'vendor_campaign_offers.status'],
            ['searchable_columns' => [], 'orderable_column' => null], // actions
        ];

        $statusColors = [
            VendorCampaignOfferStatus::PendingAdmin->value => 'warning',
            VendorCampaignOfferStatus::Active->value        => 'success',
            VendorCampaignOfferStatus::Draft->value         => 'gray',
            VendorCampaignOfferStatus::Paused->value        => 'gray',
            VendorCampaignOfferStatus::Ended->value         => 'gray',
            VendorCampaignOfferStatus::Cancelled->value     => 'danger',
        ];

        $canEdit = $admin->hasPermissionTo('campaign_offers.edit');

        return $this->dataTableResponse($request, $query, $columns, function (VendorCampaignOffer $row) use ($statusColors, $canEdit) {
            $statusColor = $statusColors[$row->status->value] ?? 'gray';
            $statusLabel = $row->status->label();
            $statusBadge = "<span class=\"inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-{$statusColor}-100 text-{$statusColor}-700\">{$statusLabel}</span>";

            $typeLabel = $row->campaign_type->label();

            $dateRange = Carbon::parse($row->starts_at)->format('d M Y')
                . ' – '
                . ($row->ends_at ? Carbon::parse($row->ends_at)->format('d M Y') : '∞');

            return [
                'vendor'     => e($row->vendor?->store_name ?? '—'),
                'name'       => e($row->name),
                'type'       => e($typeLabel),
                'commission' => number_format($row->offered_commission_rate, 1) . '%',
                'products'   => $row->products()->count(),
                'date_range' => $dateRange,
                'invited'    => $row->invitations_count,
                'status'     => $statusBadge,
                'actions'    => $this->buildRowActions($row, $canEdit),
                'DT_RowData' => ['id' => $row->id, 'status' => $row->status->value],
            ];
        });
    }

    public function show(VendorCampaignOffer $offer): View
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('campaign_offers.view'), 403);

        $offer->load([
            'vendor',
            'approvedByAdmin',
            'rejectedByAdmin',
            'products.vendorListing.product',
            'invitations.marketer',
            'invitations.resultingCampaign',
        ]);

        $conversionStats = [
            'accepted'    => $offer->invitations->where('status', VendorCampaignInvitationStatus::Accepted)->count(),
            'declined'    => $offer->invitations->whereIn('status', [VendorCampaignInvitationStatus::Declined, VendorCampaignInvitationStatus::Expired, VendorCampaignInvitationStatus::Revoked])->count(),
            'pending'     => $offer->invitations->where('status', VendorCampaignInvitationStatus::Pending)->count(),
            'conversions' => $offer->resultingCampaigns()
                ->join('marketer_conversions', 'marketer_campaigns.id', '=', 'marketer_conversions.marketer_campaign_id')
                ->count('marketer_conversions.id'),
        ];

        return view('admin.vendor-campaign-offers.show', compact('offer', 'conversionStats'));
    }

    public function approve(VendorCampaignOffer $offer): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('campaign_offers.edit'), 403);

        if ($offer->status !== VendorCampaignOfferStatus::PendingAdmin) {
            return response()->json(['message' => 'Offer is not pending admin review.'], 422);
        }

        $offer->update([
            'status'               => VendorCampaignOfferStatus::Active,
            'approved_by_admin_id' => $admin->id,
            'approved_at'          => now(),
        ]);

        // Fan out pending invitations created before approval
        $offer->invitations()->where('status', VendorCampaignInvitationStatus::Pending->value)->each(
            fn($inv) => Notification::send($inv->marketer, new VendorCampaignInvitationReceived($inv))
        );

        // Notify vendor admins
        Notification::send($offer->vendor->vendorAdmins, new VendorCampaignOfferApproved($offer));

        return response()->json(['message' => 'Campaign offer approved. Marketers have been notified.']);
    }

    public function reject(Request $request, VendorCampaignOffer $offer): JsonResponse
    {
        $admin = auth('admin')->user();
        abort_unless($admin->hasPermissionTo('campaign_offers.edit'), 403);

        if ($offer->status !== VendorCampaignOfferStatus::PendingAdmin) {
            return response()->json(['message' => 'Offer is not pending admin review.'], 422);
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $offer->update([
            'status'              => VendorCampaignOfferStatus::Draft,
            'rejected_by_admin_id'=> $admin->id,
            'rejection_reason'    => $request->rejection_reason,
        ]);

        Notification::send($offer->vendor->vendorAdmins, new VendorCampaignOfferRejected($offer));

        return response()->json(['message' => 'Campaign offer rejected. Vendor has been notified.']);
    }

    private function buildRowActions(VendorCampaignOffer $offer, bool $canEdit): string
    {
        $showUrl    = route('admin.vendor-campaign-offers.show', $offer->id);
        $approveUrl = route('admin.vendor-campaign-offers.approve', $offer->id);
        $rejectUrl  = route('admin.vendor-campaign-offers.reject', $offer->id);

        $html = '<div class="flex items-center gap-1">';
        $html .= "<a href=\"{$showUrl}\" class=\"btn btn-xs btn-secondary\">View</a>";

        if ($canEdit && $offer->status === VendorCampaignOfferStatus::PendingAdmin) {
            $html .= "<button type=\"button\" class=\"btn btn-xs btn-success js-approve-btn\" data-url=\"{$approveUrl}\" data-name=\"" . e($offer->name) . "\">Approve</button>";
            $html .= "<button type=\"button\" class=\"btn btn-xs btn-danger js-reject-btn\" data-url=\"{$rejectUrl}\" data-name=\"" . e($offer->name) . "\">Reject</button>";
        }

        $html .= '</div>';
        return $html;
    }
}
