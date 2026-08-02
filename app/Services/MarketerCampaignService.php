<?php

namespace App\Services;

use App\Jobs\ProcessCampaignAutoApproveJob;
use App\Jobs\ProcessInvitationTimeoutJob;
use App\Jobs\SendCampaignWhatsAppNotificationJob;
use App\Models\Admin;
use App\Models\MarketerCampaign;
use App\Models\MarketerCampaignInvitation;
use App\Models\MarketerCampaignSample;
use App\Models\MarketerCampaignTieredRule;
use App\Models\Vendor;
use App\Models\VendorListing;
use App\Notifications\Admin\NewCampaignPendingNotification;
use App\Notifications\Vendor\CampaignApprovedNotification;
use App\Notifications\Vendor\CampaignAutoApprovedNotification;
use App\Notifications\Vendor\CampaignDoneNotification;
use App\Notifications\Vendor\CampaignInvitationAcceptedNotification;
use App\Notifications\Vendor\CampaignInvitationRejectedNotification;
use App\Notifications\Vendor\CampaignPendingAdminNotification;
use App\Notifications\Vendor\CampaignRejectedNotification;
use App\Notifications\Vendor\MarketerReplacedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketerCampaignService
{
    /**
     * Create a new campaign from vendor panel.
     * $data keys: vendor_listing_id|admin_product_listing_id, country_id, currency,
     *             commission_type, max_commission_budget, title, notes,
     *             marketer_vendor_ids (array of UUIDs),
     *             tiered_rules (array of {from_sale_number, commission_amount} — only for tiered type)
     */
    /**
     * Mark conversions as paid when included in a payout.
     * Called from payout processing flow.
     */
    public function markConversionsPaid(array $conversionIds, string $payoutId): void
    {
        DB::transaction(function () use ($conversionIds, $payoutId) {
            \App\Models\MarketerCampaignConversion::whereIn('id', $conversionIds)
                ->where('commissioned', false)
                ->update([
                    'commissioned' => true,
                    'paid_at'      => now(),
                ]);

            $conversions = \App\Models\MarketerCampaignConversion::whereIn('id', $conversionIds)
                ->with('invitation.marketer')
                ->get();

            $earningsByMarketer = $conversions->groupBy('invitation.marketer_vendor_id');

            foreach ($earningsByMarketer as $marketerId => $marketerConversions) {
                $totalEarned = $marketerConversions->sum('commission_amount');
                $marketer    = $marketerConversions->first()->invitation->marketer;

                $profile = $marketer->marketerProfile()->firstOrCreate(
                    ['vendor_id' => $marketerId],
                    ['total_earnings' => 0, 'total_conversions' => 0]
                );

                $profile->increment('total_earnings', $totalEarned);
                $profile->increment('total_conversions', $marketerConversions->count());
            }
        });
    }

    public function createCampaign(Vendor $vendor, array $data): MarketerCampaign
    {
        return DB::transaction(function () use ($vendor, $data) {
            $category = null;

            if (isset($data['vendor_listing_id'])) {
                $listing = VendorListing::where('id', $data['vendor_listing_id'])
                    ->where('vendor_id', $vendor->id)
                    ->where('fulfillment_model', 'fbn') // Must be FBN
                    ->firstOrFail();

                $category = $listing->productVariant?->product?->category;

                $minStock = (int) ($category?->min_stock_for_campaign ?? 10);
                $availableStock = (int) $listing->warehouseInventories()->sum('quantity_available');
                if ($availableStock < $minStock) {
                    throw new \RuntimeException("Insufficient stock. Minimum {$minStock} units required to start a campaign.");
                }
            }

            $marketerVendors = Vendor::whereIn('id', $data['marketer_vendor_ids'] ?? [])
                ->whereNotNull('marketer_type')
                ->get();

            // Determine sample snapshot from category (higher of the two if mixed marketer types)
            $perMarketerSampleQty = 0;
            $platformSampleQty = 0;
            if ($category) {
                $hasInfluencer = $marketerVendors->contains('marketer_type', 'influencer');
                $hasAffiliate  = $marketerVendors->contains('marketer_type', 'affiliate');
                if ($hasInfluencer) {
                    $perMarketerSampleQty = max($perMarketerSampleQty, $category->influencer_sample_qty);
                }
                if ($hasAffiliate) {
                    $perMarketerSampleQty = max($perMarketerSampleQty, $category->affiliate_sample_qty);
                }
                $platformSampleQty = $category->platform_sample_qty;
            }

            $autoApproveHours = (int) setting('marketer_campaign_auto_approve_hours', 36);

            $campaign = MarketerCampaign::create([
                'vendor_id'                        => $vendor->id,
                'vendor_listing_id'                => $data['vendor_listing_id'] ?? null,
                'admin_product_listing_id'         => $data['admin_product_listing_id'] ?? null,
                'country_id'                       => $data['country_id'],
                'currency'                         => $data['currency'],
                'commission_type'                  => $data['commission_type'],
                'max_commission_budget'            => $data['max_commission_budget'],
                'platform_commission_amount'       => 0, // set by admin later
                'marketer_commission_amount'       => 0, // set by admin later
                'status'                           => 'pending_admin',
                'auto_approve_at'                  => now()->addHours($autoApproveHours),
                'auto_approved'                    => false,
                'platform_sample_qty_snapshot'     => $platformSampleQty,
                'per_marketer_sample_qty_snapshot' => $perMarketerSampleQty,
                'requested_marketer_vendor_ids'    => $marketerVendors->pluck('id')->values()->all(),
                'title'                            => $data['title'] ?? null,
                'notes'                            => $data['notes'] ?? null,
            ]);

            if ($data['commission_type'] === 'tiered' && !empty($data['tiered_rules'])) {
                foreach ($data['tiered_rules'] as $i => $rule) {
                    MarketerCampaignTieredRule::create([
                        'campaign_id'       => $campaign->id,
                        'from_sale_number'  => $rule['from_sale_number'],
                        'commission_amount' => $rule['commission_amount'],
                        'currency'          => $data['currency'],
                        'sort_order'        => $i,
                    ]);
                }
            }

            if (isset($listing)) {
                $listing->update(['campaign_enabled' => true]);
            }

            Admin::query()->get()->each(fn ($admin) => $admin->notify(new NewCampaignPendingNotification($campaign)));
            $vendor->vendorAdmins->each(fn ($va) => $va->notify(new CampaignPendingAdminNotification($campaign)));

            ProcessCampaignAutoApproveJob::dispatch($campaign->id)
                ->delay(now()->addHours($autoApproveHours));

            return $campaign;
        });
    }

    /**
     * Admin approves a campaign: sets commission split, dispatches invitations to marketers.
     */
    public function approveCampaign(
        MarketerCampaign $campaign,
        int $platformCommission,
        int $marketerCommission,
        Admin $admin,
        array $marketerVendorIds
    ): void {
        DB::transaction(function () use ($campaign, $platformCommission, $marketerCommission, $admin, $marketerVendorIds) {
            $campaign->update([
                'status'                     => 'active',
                'platform_commission_amount' => $platformCommission,
                'marketer_commission_amount' => $marketerCommission,
                'reviewed_by_admin_id'       => $admin->id,
                'reviewed_at'                => now(),
            ]);

            foreach ($marketerVendorIds as $marketerId) {
                $this->dispatchInvitation($campaign, $marketerId);
            }

            if ($campaign->platform_sample_qty_snapshot > 0) {
                MarketerCampaignSample::create([
                    'campaign_id'   => $campaign->id,
                    'invitation_id' => null,
                    'sample_owner'  => 'platform',
                    'quantity'      => $campaign->platform_sample_qty_snapshot,
                    'status'        => 'pending',
                ]);
            }

            $campaign->vendor->vendorAdmins->each(
                fn ($va) => $va->notify(new CampaignApprovedNotification($campaign, count($marketerVendorIds)))
            );
        });
    }

    /**
     * Admin rejects a campaign.
     */
    public function rejectCampaign(MarketerCampaign $campaign, Admin $admin, string $reason): void
    {
        $campaign->update([
            'status'               => 'rejected',
            'reviewed_by_admin_id' => $admin->id,
            'reviewed_at'          => now(),
            'rejection_reason'     => $reason,
        ]);

        $campaign->vendor->vendorAdmins->each(fn ($va) => $va->notify(new CampaignRejectedNotification($campaign)));
    }

    /**
     * Auto-approve a campaign (called by job after the configured timeout).
     */
    public function autoApproveCampaign(string $campaignId): void
    {
        $campaign = MarketerCampaign::where('id', $campaignId)
            ->where('status', 'pending_admin')
            ->where('auto_approved', false)
            ->first();

        if (!$campaign) {
            return;
        }

        DB::transaction(function () use ($campaign) {
            $campaign->update([
                'status'        => 'auto_approved',
                'auto_approved' => true,
                'reviewed_at'   => now(),
            ]);

            // NOTE: auto-approved campaigns have no commission split set by admin.
            // Commissions are taken from category/country settings at conversion time.

            foreach ($campaign->requested_marketer_vendor_ids ?? [] as $marketerId) {
                $this->dispatchInvitation($campaign, $marketerId);
            }

            if ($campaign->platform_sample_qty_snapshot > 0) {
                MarketerCampaignSample::create([
                    'campaign_id'   => $campaign->id,
                    'invitation_id' => null,
                    'sample_owner'  => 'platform',
                    'quantity'      => $campaign->platform_sample_qty_snapshot,
                    'status'        => 'pending',
                ]);
            }

            $campaign->vendor->vendorAdmins->each(fn ($va) => $va->notify(new CampaignAutoApprovedNotification($campaign)));
        });
    }

    /**
     * Dispatch a campaign invitation to a marketer vendor.
     */
    public function dispatchInvitation(MarketerCampaign $campaign, string $marketerVendorId): MarketerCampaignInvitation
    {
        $timeoutHours = (int) setting('marketer_invitation_timeout_hours', 12);
        $referralCode = strtoupper(Str::random(10));

        $invitation = MarketerCampaignInvitation::create([
            'campaign_id'             => $campaign->id,
            'marketer_vendor_id'      => $marketerVendorId,
            'status'                  => 'pending',
            'acceptance_window_hours' => $timeoutHours,
            'expires_at'              => now()->addHours($timeoutHours),
            'referral_code'           => $referralCode,
            // VERIFY: route once the customer-facing referral tracking endpoint exists
            'referral_link'           => url("/r/{$referralCode}"),
        ]);

        // Generate QR code for referral link
        try {
            $qrCode = \Endroid\QrCode\QrCode::create($invitation->referral_link)
                ->setSize(300)
                ->setMargin(10);
            $writer  = new \Endroid\QrCode\Writer\PngWriter();
            $result  = $writer->write($qrCode);
            $qrPath  = 'qrcodes/invitations/' . $invitation->id . '.png';
            \Illuminate\Support\Facades\Storage::disk('public')->put($qrPath, $result->getString());
            $invitation->update(['qr_code_path' => $qrPath]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('QR generation failed for invitation ' . $invitation->id . ': ' . $e->getMessage());
        }

        ProcessInvitationTimeoutJob::dispatch($invitation->id)
            ->delay(now()->addHours($timeoutHours));

        SendCampaignWhatsAppNotificationJob::dispatch($invitation->id);

        if ($campaign->per_marketer_sample_qty_snapshot > 0) {
            MarketerCampaignSample::create([
                'campaign_id'   => $campaign->id,
                'invitation_id' => $invitation->id,
                'sample_owner'  => 'marketer',
                'quantity'      => $campaign->per_marketer_sample_qty_snapshot,
                'status'        => 'pending',
            ]);
        }

        return $invitation;
    }

    /**
     * Marketer accepts an invitation.
     */
    public function acceptInvitation(MarketerCampaignInvitation $invitation, ?string $marketerNote = null): void
    {
        if (!$invitation->isPending()) {
            throw new \RuntimeException('Invitation is no longer pending.');
        }

        $invitation->update([
            'status'        => 'accepted',
            'responded_at'  => now(),
            'marketer_note' => $marketerNote,
        ]);

        $invitation->campaign->vendor->vendorAdmins->each(
            fn ($va) => $va->notify(new CampaignInvitationAcceptedNotification($invitation))
        );
    }

    /**
     * Marketer rejects an invitation — triggers replacement logic.
     */
    public function rejectInvitation(MarketerCampaignInvitation $invitation, ?string $reason = null): void
    {
        if (!$invitation->isPending()) {
            throw new \RuntimeException('Invitation is no longer pending.');
        }

        $invitation->update([
            'status'         => 'rejected',
            'responded_at'   => now(),
            'decline_reason' => $reason,
        ]);

        $this->replaceMarketer($invitation);
    }

    /**
     * Handle timeout — called by ProcessInvitationTimeoutJob.
     */
    public function handleInvitationTimeout(string $invitationId): void
    {
        $invitation = MarketerCampaignInvitation::find($invitationId);
        if (!$invitation || !$invitation->isPending()) {
            return;
        }

        $invitation->update(['status' => 'timed_out', 'responded_at' => now()]);
        $this->replaceMarketer($invitation);
    }

    /**
     * Find a replacement marketer (same type, min accepted campaigns) and dispatch new invitation.
     */
    protected function replaceMarketer(MarketerCampaignInvitation $oldInvitation): void
    {
        $campaign    = $oldInvitation->campaign;
        $oldMarketer = $oldInvitation->marketer;

        $alreadyInvited = $campaign->invitations()->pluck('marketer_vendor_id')->toArray();
        $minAccepted    = (int) setting('marketer_replacement_min_accepted_campaigns', 0);

        $replacement = Vendor::where('marketer_type', $oldMarketer->marketer_type)
            ->whereNotIn('id', $alreadyInvited)
            ->where('global_status', 'active')
            ->withCount(['campaignInvitations as accepted_count' => fn ($q) => $q->where('status', 'accepted')])
            ->having('accepted_count', '>=', $minAccepted)
            ->orderBy('accepted_count', 'desc')
            ->first();

        $campaign->vendor->vendorAdmins->each(
            fn ($va) => $va->notify(new CampaignInvitationRejectedNotification($oldInvitation, (bool) $replacement))
        );

        if (!$replacement) {
            return; // No replacement available
        }

        $newInvitation = $this->dispatchInvitation($campaign, $replacement->id);
        $newInvitation->update(['replaced_invitation_id' => $oldInvitation->id]);

        $campaign->vendor->vendorAdmins->each(
            fn ($va) => $va->notify(new MarketerReplacedNotification($campaign, $oldMarketer, $replacement))
        );
    }

    /**
     * Mark a campaign as done when product stock hits zero.
     */
    public function markCampaignDone(MarketerCampaign $campaign): void
    {
        $campaign->update(['status' => 'done']);

        $totalConversions       = (int) $campaign->conversions()->count();
        $totalCommissionEarned  = (float) $campaign->conversions()->sum('commission_amount');

        $campaign->vendor->vendorAdmins->each(
            fn ($va) => $va->notify(new CampaignDoneNotification($campaign, $totalConversions, $totalCommissionEarned))
        );

        $campaign->invitations()->where('status', 'accepted')->with('marketer.vendorAdmins')->get()
            ->each(fn ($inv) => $inv->marketer->vendorAdmins->each(
                fn ($va) => $va->notify(new CampaignDoneNotification(
                    $campaign,
                    (int) $inv->total_conversions,
                    (float) $inv->total_commission_earned
                ))
            ));
    }
}
