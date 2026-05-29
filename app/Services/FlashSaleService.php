<?php

namespace App\Services;

use App\Jobs\FlashSaleInviteBulkJob;
use App\Jobs\SubmissionApprovedNotificationJob;
use App\Jobs\SubmissionRejectedNotificationJob;
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
    // CRUD
    // ─────────────────────────────────────────────────────────────────────────

    public function create(array $data, Admin $admin): FlashSale
    {
        $this->validateTimeline($data);

        return FlashSale::create([
            ...$data,
            'status' => 'draft',
            'created_by_admin_id' => $admin->id,
            'updated_by_admin_id' => $admin->id,
            'approved_slots_count' => 0,
            'eligible_categories' => $data['eligible_categories'] ?? null,
            'eligible_seller_tiers' => $data['eligible_seller_tiers'] ?? null,
        ]);
    }

    public function update(FlashSale $sale, array $data, Admin $admin): FlashSale
    {
        if (in_array($sale->status, ['live', 'ended', 'cancelled'], true)) {
            throw new \LogicException("Cannot update a {$sale->status} flash sale.");
        }

        $sale->update([
            ...$data,
            'updated_by_admin_id' => $admin->id,
        ]);

        return $sale->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Status transitions
    // ─────────────────────────────────────────────────────────────────────────

    public function transition(FlashSale $sale, string $newStatus, Admin $admin, string $reason = ''): FlashSale
    {
        if (!$sale->canTransitionTo($newStatus)) {
            throw new \LogicException("Cannot transition from '{$sale->status}' to '{$newStatus}'.");
        }

        $updates = [
            'status' => $newStatus,
            'updated_by_admin_id' => $admin->id,
        ];

        if ($newStatus === 'cancelled') {
            $updates['cancelled_at'] = now();
            $updates['cancellation_reason'] = $reason;
        }

        $sale->update($updates);
        return $sale->fresh();
    }

    public function openSubmissions(FlashSale $sale, Admin $admin): FlashSale
    {
        $sale = $this->transition($sale, 'submission_open', $admin);
        $this->inviteEligibleVendors($sale);
        return $sale;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Vendor invitations
    // ─────────────────────────────────────────────────────────────────────────

    public function inviteEligibleVendors(FlashSale $sale): int
    {
        $query = Vendor::where('global_status', 'active');

        if ($sale->country_id) {
            $query->where('country_id', $sale->country_id);
        }

        if (!empty($sale->eligible_seller_tiers)) {
            // Vendors table has no tier column; use total_sales as proxy:
            //   bronze: < 10 000  |  silver: 10 000–50 000
            //   gold: 50 000–200 000  |  platinum: > 200 000
            $query->where(function ($q) use ($sale) {
                foreach ($sale->eligible_seller_tiers as $tier) {
                    $q->orWhere(function ($inner) use ($tier) {
                        match ($tier) {
                            'bronze' => $inner->where('total_sales', '<', 10000),
                            'silver' => $inner->whereBetween('total_sales', [10000, 50000]),
                            'gold' => $inner->whereBetween('total_sales', [50001, 200000]),
                            'platinum' => $inner->where('total_sales', '>', 200000),
                            default => null,
                        };
                    });
                }
            });
        }

        if ($sale->min_seller_rating !== null) {
            $query->where('store_rating_avg', '>=', $sale->min_seller_rating);
        }

        $alreadyInvited = FlashSaleVendorInvitition::where('flash_sale_id', $sale->id)->pluck('vendor_id');
        if ($alreadyInvited->isNotEmpty()) {
            $query->whereNotIn('id', $alreadyInvited);
        }

        $newIds = [];
        $query->chunkById(200, function ($vendors) use ($sale, &$newIds) {
            foreach ($vendors as $vendor) {
                FlashSaleVendorInvitition::create([
                    'flash_sale_id' => $sale->id,
                    'vendor_id' => $vendor->id,
                    'invitation_type' => 'auto',
                    'status' => 'pending',
                    'invited_at' => now(),
                    'slots_allocated' => $sale->max_products_per_seller ?? null,
                ]);
                $newIds[] = $vendor->id;
            }
        });

        if (!empty($newIds)) {
            FlashSaleInviteBulkJob::dispatch($sale->id, $newIds);
        }

        return count($newIds);
    }

    public function countEligibleVendors(FlashSale $sale): int
    {
        return Vendor::where('global_status', 'active')
            ->when($sale->country_id, fn($q) => $q->where('country_id', $sale->country_id))
            ->when($sale->min_seller_rating, fn($q) => $q->where('store_rating_avg', '>=', $sale->min_seller_rating))
            ->count();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Submission review
    // ─────────────────────────────────────────────────────────────────────────

    public function reviewSubmission(
        FlashSaleSubmission $submission,
        string $decision,
        array $data,
        Admin $admin
    ): FlashSaleSubmission {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            throw new \InvalidArgumentException("Decision must be 'approved' or 'rejected'.");
        }

        $flashSale = $submission->flashSale;

        if ($flashSale->status === 'live') {
            throw new \LogicException('Cannot review submissions while the sale is live.');
        }

        $fromStatus = $submission->status;

        DB::transaction(function () use ($submission, $decision, $data, $admin, $flashSale, $fromStatus) {
            if ($decision === 'approved') {
                if ((float) $submission->calculated_discount_pct < (float) $flashSale->min_discount_pct) {
                    throw new \LogicException('Discount too low. Minimum required: ' . $flashSale->min_discount_pct . '%');
                }
                if ($flashSale->max_total_slots && $flashSale->approved_slots_count >= $flashSale->max_total_slots) {
                    throw new \LogicException('Slot limit reached for this flash sale.');
                }

                $submission->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'reviewed_by_admin_id' => $admin->id,
                    'approved_at' => now(),
                    'admin_notes' => $data['admin_notes'] ?? null,
                ]);

                $flashSale->increment('approved_slots_count');
                SubmissionApprovedNotificationJob::dispatch($submission->id);
            } else {
                if (empty($data['rejection_code'])) {
                    throw new \InvalidArgumentException('rejection_code is required when rejecting a submission.');
                }

                $submission->update([
                    'status' => 'rejected',
                    'reviewed_at' => now(),
                    'reviewed_by_admin_id' => $admin->id,
                    'rejection_reason' => $data['rejection_reason'] ?? null,
                    'rejection_code' => $data['rejection_code'],
                    'admin_notes' => $data['admin_notes'] ?? null,
                ]);

                SubmissionRejectedNotificationJob::dispatch($submission->id);
            }

            FlashSaleSubmissionHistory::create([
                'flash_sale_submission_id' => $submission->id,
                'from_status' => $fromStatus,
                'to_status' => $decision === 'approved' ? 'approved' : 'rejected',
                'changed_by_user_id' => $admin->id,
                'changed_by_role' => 'admin',
                'reason' => $data['rejection_reason'] ?? ($data['admin_notes'] ?? null),
            ]);
        });

        return $submission->fresh();
    }

    public function bulkReview(array $submissionIds, string $decision, array $data, Admin $admin): array
    {
        $result = ['approved' => 0, 'rejected' => 0, 'failed' => 0];

        foreach ($submissionIds as $id) {
            $submission = FlashSaleSubmission::find($id);
            if (!$submission) {
                $result['failed']++;
                continue;
            }
            try {
                $this->reviewSubmission($submission, $decision, $data, $admin);
                $result[$decision === 'approved' ? 'approved' : 'rejected']++;
            } catch (\Throwable) {
                $result['failed']++;
            }
        }

        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function validateTimeline(array $data): void
    {
        $fields = [
            'submission_opens_at' => 'Submission opens',
            'submission_closes_at' => 'Submission closes',
            'review_deadline_at' => 'Review deadline',
            'sale_starts_at' => 'Sale starts',
            'sale_ends_at' => 'Sale ends',
        ];

        $timestamps = array_map(
            fn($k) => isset($data[$k]) ? strtotime($data[$k]) : null,
            array_flip($fields)
        );

        $keys = array_keys($fields);
        $values = array_values($timestamps);

        for ($i = 0; $i < count($keys) - 1; $i++) {
            if ($values[$i] !== null && $values[$i + 1] !== null && $values[$i] >= $values[$i + 1]) {
                throw new \InvalidArgumentException(
                    array_values($fields)[$i] . ' must be before ' . array_values($fields)[$i + 1]
                );
            }
        }
    }
}
