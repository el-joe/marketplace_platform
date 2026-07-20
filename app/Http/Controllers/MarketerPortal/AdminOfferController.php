<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Enums\AdminMarketerInvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminMarketerInvitation;
use App\Models\MarketerCampaign;
use App\Notifications\Admin\AdminMarketerInvitationAccepted;
use App\Notifications\Admin\AdminMarketerInvitationDeclined;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminOfferController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $pendingCount = AdminMarketerInvitation::where('marketer_id', $marketer->id)
            ->where('status', AdminMarketerInvitationStatus::Pending)
            ->count();

        $acceptedCount = AdminMarketerInvitation::where('marketer_id', $marketer->id)
            ->where('status', AdminMarketerInvitationStatus::Accepted)
            ->count();

        $totalEarnedFromAdminCampaigns = DB::table('marketer_conversions')
            ->join('admin_marketer_invitations', 'admin_marketer_invitations.resulting_campaign_id', '=', 'marketer_conversions.campaign_id')
            ->where('admin_marketer_invitations.marketer_id', $marketer->id)
            ->where('marketer_conversions.status', '!=', 'reversed')
            ->sum('marketer_conversions.commission_amount');

        $invitations = AdminMarketerInvitation::where('marketer_id', $marketer->id)
            ->with('admin:id,name')
            ->orderByRaw("FIELD(status, 'pending', 'accepted', 'declined', 'expired', 'revoked')")
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('marketer.admin-offers.index', [
            'marketer' => $marketer,
            'invitations' => $invitations,
            'pendingCount' => $pendingCount,
            'acceptedCount' => $acceptedCount,
            'totalEarnedFromAdminCampaigns' => $totalEarnedFromAdminCampaigns,
        ]);
    }

    public function show(AdminMarketerInvitation $invitation): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($invitation->marketer_id !== $marketer->id, 403);

        $invitation->load(['admin', 'resultingCampaign']);

        return view('marketer.admin-offers.show', compact('marketer', 'invitation'));
    }

    public function accept(Request $request, AdminMarketerInvitation $invitation): RedirectResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($invitation->marketer_id !== $marketer->id, 403);

        if ($invitation->status !== AdminMarketerInvitationStatus::Pending) {
            return back()->with('error', __('marketer.admin_offers.invitation_not_pending'));
        }

        $request->validate(['marketer_note' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($invitation, $request, $marketer) {
            $slug = Str::lower($marketer->referral_code . '-' . Str::random(6));

            $campaign = MarketerCampaign::create([
                'marketer_id' => $marketer->id,
                'vendor_id' => null,
                'name' => $invitation->title,
                'description' => $invitation->description,
                'campaign_type' => $invitation->campaign_type->value,
                // marketer_campaigns has no "pending" status — 'draft' is what
                // goes through the normal admin review/approval flow.
                'status' => 'draft',
                'commission_rate' => $invitation->commission_rate_percent,
                'commission_type' => $invitation->commission_type->toCommissionType()->value,
                'budget' => $invitation->budget,
                'starts_at' => $invitation->starts_at ?? now(),
                'ends_at' => $invitation->ends_at ?? now()->addYear(),
                'tracking_url_slug' => $slug,
            ]);

            $invitation->update([
                'status' => 'accepted',
                'marketer_note' => $request->marketer_note,
                'responded_at' => now(),
                'resulting_campaign_id' => $campaign->id,
            ]);
        });

        $invitation->refresh();

        Cache::forget("marketer_badges_{$marketer->id}");

        Notification::send($invitation->admin, new AdminMarketerInvitationAccepted($invitation));

        return redirect()->route('marketer.admin-offers.show', $invitation->id)
            ->with('success', __('marketer.admin_offers.accepted_message'));
    }

    public function decline(Request $request, AdminMarketerInvitation $invitation): RedirectResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($invitation->marketer_id !== $marketer->id, 403);

        if ($invitation->status !== AdminMarketerInvitationStatus::Pending) {
            return back()->with('error', __('marketer.admin_offers.invitation_not_pending'));
        }

        $request->validate(['marketer_note' => 'required|string|max:1000']);

        $invitation->update([
            'status' => 'declined',
            'marketer_note' => $request->marketer_note,
            'responded_at' => now(),
        ]);

        Cache::forget("marketer_badges_{$marketer->id}");

        Notification::send($invitation->admin, new AdminMarketerInvitationDeclined($invitation));

        return back()->with('success', __('marketer.admin_offers.declined_message'));
    }
}
