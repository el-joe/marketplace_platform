<?php

namespace App\Http\View\Composers;

use App\Models\AdminMarketerInvitation;
use App\Models\MarketerCampaign;
use App\Models\MarketerSampleRequest;
use App\Models\VendorCampaignInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class MarketerSidebarComposer
{
    public function compose(View $view): void
    {
        $marketerId = Auth::guard('marketer')->id();

        if (! $marketerId) {
            $view->with('badges', []);
            return;
        }

        $badges = Cache::remember("marketer_badges_{$marketerId}", 60, function () use ($marketerId) {
            return [
                'pending_campaigns' => MarketerCampaign::where('marketer_id', $marketerId)
                    ->where('status', 'pending_review')->count(),
                'pending_invitations' => VendorCampaignInvitation::where('marketer_id', $marketerId)
                    ->where('status', 'pending')
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->count(),
                'pending_sample_requests' => MarketerSampleRequest::where('marketer_id', $marketerId)
                    ->where('status', 'requested')->count(),
                'pending_admin_offers' => AdminMarketerInvitation::where('marketer_id', $marketerId)
                    ->where('status', 'pending')->count(),
                'unread_notifications' => Auth::guard('marketer')->user()
                    ->unreadNotifications()->count(),
            ];
        });

        $view->with('badges', $badges);
    }
}
