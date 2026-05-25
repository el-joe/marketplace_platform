<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\FlashSale;
use App\Models\FlashSaleSubmission;
use App\Models\FlashSaleSubmissionHistory;
use App\Models\FlashSaleVendorInvitition;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class FlashSaleService
{
    // ─────────────────────────────────────────────────────────────────────────
    // Sale lifecycle transitions
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Transition a flash sale to 'open' status, enabling vendor submissions.
     */
    public function openSubmissions(FlashSale $sale, Admin $admin): void
    {
        if (!$sale->canTransitionTo('open')) {
            throw new \LogicException("Cannot open submissions for a {$sale->status} flash sale.");
        }

        $sale->update([
            'status'             => 'open',
            'updated_by_admin_id' => $admin->id,
        ]);
    }

    /**
     * Close the submission window and move sale to 'review' status.
     */
    public function closeSubmissions(FlashSale $sale, Admin $admin): void
    {
        if (!$sale->canTransitionTo('review')) {
            throw new \LogicException("Cannot close submissions for a {$sale->status} flash sale.");
        }

        $sale->update([
            'status'             => 'review',
            'updated_by_admin_id' => $admin->id,
        ]);
    }

    /**
     * Mark the sale as scheduled (approved and awaiting launch).
     */
    public function scheduleSale(FlashSale $sale, Admin $admin): void
    {
        if (!$sale->canTransitionTo('scheduled')) {
            throw new \LogicException("Cannot schedule a {$sale->status} flash sale.");
        }

        $sale->update([
            'status'             => 'scheduled',
            'updated_by_admin_id' => $admin->id,
        ]);
    }

    /**
     * Launch the sale (transition to 'live').
     */
    public function launchSale(FlashSale $sale, Admin $admin): void
    {
        if (!$sale->canTransitionTo('live')) {
            throw new \LogicException("Cannot launch a {$sale->status} flash sale.");
        }

        DB::transaction(function () use ($sale, $admin) {
            $sale->update([
                'status'             => 'live',
                'updated_by_admin_id' => $admin->id,
            ]);

            // Activate all approved submissions
            FlashSaleSubmission::where('flash_sale_id', $sale->id)
                ->where('status', 'approved')
                ->update(['status' => 'active']);
        });
    }

    /**
     * End a live sale.
     */
    public function endSale(FlashSale $sale, Admin $admin): void
    {
        if (!$sale->canTransitionTo('ended')) {
            throw new \LogicException("Cannot end a {$sale->status} flash sale.");
        }

        DB::transaction(function () use ($sale, $admin) {
            $sale->update([
                'status'             => 'ended',
                'updated_by_admin_id' => $admin->id,
            ]);

            // End all active submissions
            FlashSaleSubmission::where('flash_sale_id', $sale->id)
                ->where('status', 'active')
                ->update(['status' => 'ended']);
        });
    }

    /**
     * Cancel a flash sale.
     */
    public function cancelSale(FlashSale $sale, Admin $admin, string $reason = ''): void
    {
        if (!$sale->canTransitionTo('cancelled')) {
            throw new \LogicException("Cannot cancel a {$sale->status} flash sale.");
        }

        $sale->update([
            'status'             => 'cancelled',
            'updated_by_admin_id' => $admin->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vendor invitations
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Invite all eligible vendors based on the flash sale's eligibility rules.
     */
    public function inviteEligibleVendors(FlashSale $sale): int
    {
        $query = Vendor::where('status', 'active');

        // Filter by eligible vendor tiers if set
        if (!empty($sale->eligible_vendor_tiers)) {
            $query->whereIn('tier', $sale->eligible_vendor_tiers);
        }

        // Filter by minimum rating if set
        if ($sale->min_vendor_rating !== null) {
            $query->where('rating', '>=', $sale->min_vendor_rating);
        }

        // Exclude vendors already invited
        $alreadyInvited = FlashSaleVendorInvitition::where('flash_sale_id', $sale->id)
            ->pluck('vendor_id');

        if ($alreadyInvited->isNotEmpty()) {
            $query->whereNotIn('id', $alreadyInvited);
        }

        $count = 0;
        $query->chunkById(100, function ($vendors) use ($sale, &$count) {
            foreach ($vendors as $vendor) {
                FlashSaleVendorInvitition::create([
                    'flash_sale_id'   => $sale->id,
                    'vendor_id'       => $vendor->id,
                    'invitation_type' => 'auto',
                    'status'          => 'pending',
                    'invited_at'      => now(),
                    'slots_allocated' => $sale->max_products_per_vendor ?? 0,
                ]);
                $count++;
            }
        });

        return $count;
    }

    /**
     * Invite a specific vendor manually.
     */
    public function inviteVendor(FlashSale $sale, Vendor $vendor, int $slotsAllocated = 0): FlashSaleVendorInvitition
    {
        return FlashSaleVendorInvitition::firstOrCreate(
            ['flash_sale_id' => $sale->id, 'vendor_id' => $vendor->id],
            [
                'invitation_type' => 'manual',
                'status'          => 'pending',
                'invited_at'      => now(),
                'slots_allocated' => $slotsAllocated ?: ($sale->max_products_per_vendor ?? 0),
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Submission management
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Approve a vendor submission.
     */
    public function approveSubmission(
        FlashSaleSubmission $submission,
        Admin $admin,
        ?string $notes = null
    ): void {
        if (!$submission->canTransitionTo('approved')) {
            throw new \LogicException("Cannot approve a {$submission->status} submission.");
        }

        DB::transaction(function () use ($submission, $admin, $notes) {
            $oldStatus = $submission->status;

            $submission->update([
                'status'               => 'approved',
                'reviewed_at'          => now(),
                'reviewed_by_admin_id' => $admin->id,
                'approved_at'          => now(),
            ]);

            FlashSaleSubmissionHistory::create([
                'flash_sale_submission_id' => $submission->id,
                'from_status'              => $oldStatus,
                'to_status'                => 'approved',
                'changed_by_user_id'       => $admin->id,
                'change_reason'            => $notes,
            ]);

            // Increment approved slot count on the parent flash sale
            $submission->flashSale()->increment('approved_slots_count');
        });
    }

    /**
     * Reject a vendor submission.
     */
    public function rejectSubmission(
        FlashSaleSubmission $submission,
        Admin $admin,
        string $reason,
        string $rejectionCode = 'manual_rejection'
    ): void {
        if (!$submission->canTransitionTo('rejected')) {
            throw new \LogicException("Cannot reject a {$submission->status} submission.");
        }

        DB::transaction(function () use ($submission, $admin, $reason, $rejectionCode) {
            $oldStatus = $submission->status;

            $submission->update([
                'status'               => 'rejected',
                'rejection_reason'     => $reason,
                'rejection_code'       => $rejectionCode,
                'reviewed_at'          => now(),
                'reviewed_by_admin_id' => $admin->id,
            ]);

            FlashSaleSubmissionHistory::create([
                'flash_sale_submission_id' => $submission->id,
                'from_status'              => $oldStatus,
                'to_status'                => 'rejected',
                'changed_by_user_id'       => $admin->id,
                'change_reason'            => $reason,
            ]);
        });
    }
}
