<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerCampaign;
use App\Models\MarketerCampaignProduct;
use App\Models\VendorCampaignInvitation;
use App\Notifications\Vendor\MarketerAcceptedInvitation;
use App\Notifications\Vendor\MarketerDeclinedInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $pendingCount = VendorCampaignInvitation::where('marketer_id', $marketer->id)
            ->where('status', 'pending')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->count();

        $acceptedThisMonth = VendorCampaignInvitation::where('marketer_id', $marketer->id)
            ->where('status', 'accepted')
            ->whereMonth('responded_at', now()->month)
            ->whereYear('responded_at', now()->year)
            ->count();

        $invitations = VendorCampaignInvitation::where('marketer_id', $marketer->id)
            ->with(['offer.vendor', 'offer.products.vendorListing.product'])
            ->orderByRaw("FIELD(status, 'pending', 'accepted', 'declined', 'expired', 'revoked')")
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('marketer.invitations.index', compact('marketer', 'invitations', 'pendingCount', 'acceptedThisMonth'));
    }

    public function show(VendorCampaignInvitation $invitation): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($invitation->marketer_id !== $marketer->id, 403);

        $invitation->load(['offer.vendor', 'offer.products.vendorListing.product', 'resultingCampaign']);

        return view('marketer.invitations.show', compact('marketer', 'invitation'));
    }

    public function accept(Request $request, VendorCampaignInvitation $invitation): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        if ($invitation->marketer_id !== $marketer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => __('marketer.invitations.invitation_not_pending')], 422);
        }

        if ($invitation->isExpired()) {
            return response()->json(['success' => false, 'message' => __('marketer.invitations.invitation_expired')], 422);
        }

        $request->validate(['marketer_note' => 'nullable|string|max:500']);

        DB::transaction(function () use ($invitation, $request, $marketer) {
            $offer = $invitation->offer;

            $slug = Str::slug($marketer->referral_code . '-' . Str::random(6));

            $campaign = MarketerCampaign::create([
                'marketer_id'             => $marketer->id,
                'vendor_id'               => $offer->vendor_id,
                'campaignable_type'       => \App\Models\Vendor::class,
                'campaignable_id'         => $offer->vendor_id,
                'name'                    => $offer->name,
                'description'             => $offer->description,
                'campaign_type'           => $offer->campaign_type,
                'status'                  => 'active',
                'commission_rate'         => $offer->offered_commission_rate,
                'commission_type'         => $offer->commission_type,
                'budget_cents'            => $offer->budget_per_marketer_cents,
                'starts_at'               => $offer->starts_at,
                'ends_at'                 => $offer->ends_at,
                'tracking_url_slug'       => $slug,
                'attribution_model'       => $offer->attribution_model,
                'whatsapp_sharing_enabled'=> $offer->whatsapp_sharing_enabled,
                'approved_at'             => now(),
                'auto_approved'           => true,
            ]);

            foreach ($offer->products as $offerProduct) {
                MarketerCampaignProduct::create([
                    'campaign_id'         => $campaign->id,
                    'vendor_listing_id'   => $offerProduct->vendor_listing_id,
                    'position'            => $offerProduct->position,
                    'commission_override' => $offerProduct->commission_override,
                ]);
            }

            $invitation->update([
                'status'                => 'accepted',
                'marketer_note'         => $request->marketer_note,
                'responded_at'          => now(),
                'resulting_campaign_id' => $campaign->id,
            ]);
        });

        $invitation->refresh();

        Notification::send($invitation->offer->vendor->vendorAdmins, new MarketerAcceptedInvitation($invitation));

        return response()->json([
            'success'  => true,
            'message'  => __('marketer.invitations.accepted_message'),
            'redirect' => route('marketer.campaigns.show', $invitation->resulting_campaign_id),
        ]);
    }

    public function decline(Request $request, VendorCampaignInvitation $invitation): JsonResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        if ($invitation->marketer_id !== $marketer->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json(['success' => false, 'message' => __('marketer.invitations.invitation_not_pending')], 422);
        }

        if ($invitation->isExpired()) {
            return response()->json(['success' => false, 'message' => __('marketer.invitations.invitation_expired')], 422);
        }

        $request->validate(['marketer_note' => 'nullable|string|max:500']);

        $invitation->update([
            'status'       => 'declined',
            'marketer_note'=> $request->marketer_note,
            'responded_at' => now(),
        ]);

        Notification::send($invitation->offer->vendor->vendorAdmins, new MarketerDeclinedInvitation($invitation));

        return response()->json(['success' => true, 'message' => __('marketer.invitations.declined_message')]);
    }
}
