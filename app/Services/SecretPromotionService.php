<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Marketer;
use App\Models\MarketerConversion;
use App\Models\MarketerSecretPromotion;
use App\Models\VendorListing;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class SecretPromotionService
{
    // ── Create ────────────────────────────────────────────────────────────────

    public function create(array $data, Admin $admin): MarketerSecretPromotion
    {
        $this->validateCommissionSplit($data);
        $this->validateListing($data['vendor_listing_id'], $data['vendor_id'] ?? null);
        $this->validateNoDuplicate($data['vendor_listing_id'], $data['marketer_id'] ?? null);

        $minFloor = (float) setting('min_marketer_commission', 5.0);
        $promo = MarketerSecretPromotion::create([
            'vendor_id' => $data['vendor_id'],
            'vendor_listing_id' => $data['vendor_listing_id'],
            'marketer_id' => $data['marketer_id'] ?: null,
            'product_value_cents' => (int) round($data['product_value'] * 100),
            'total_commission_pct' => $data['total_commission_pct'],
            'marketer_share_pct' => $data['marketer_share_pct'],
            'admin_share_pct' => round($data['total_commission_pct'] - $data['marketer_share_pct'], 2),
            'min_commission_pct' => $minFloor,
            'is_hidden_from_public' => 1,
            'status' => 'active',
            'valid_until' => $data['valid_until'] ?? null,
            'created_by_vendor' => 0,
            'approved_by_admin_id' => $admin->id,
        ]);

        app(ActivityLoggerService::class)->log(
            description: 'Secret promotion created for '
            . ($promo->vendor?->store_name ?? 'unknown vendor')
            . ' — ' . ($promo->vendorListing?->productVariant?->product?->name_en ?? 'unknown listing'),
            subject: $promo,
            causer: $admin,
            event: 'created',
        );

        return $promo;
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(MarketerSecretPromotion $promo, array $data, Admin $admin): MarketerSecretPromotion
    {
        if ($promo->status === 'expired') {
            throw ValidationException::withMessages([
                'status' => 'Cannot edit an expired promotion.',
            ]);
        }

        $this->validateCommissionSplit($data);
        $this->validateListing($data['vendor_listing_id'], $data['vendor_id'] ?? null);

        // Allow duplicate check only if listing/marketer changed
        if (
            $data['vendor_listing_id'] !== $promo->vendor_listing_id
            || ($data['marketer_id'] ?? null) !== $promo->marketer_id
        ) {
            $this->validateNoDuplicate($data['vendor_listing_id'], $data['marketer_id'] ?? null, $promo->id);
        }

        $promo->update([
            'vendor_id' => $data['vendor_id'],
            'vendor_listing_id' => $data['vendor_listing_id'],
            'marketer_id' => $data['marketer_id'] ?: null,
            'product_value_cents' => (int) round($data['product_value'] * 100),
            'total_commission_pct' => $data['total_commission_pct'],
            'marketer_share_pct' => $data['marketer_share_pct'],
            'admin_share_pct' => round($data['total_commission_pct'] - $data['marketer_share_pct'], 2),
            'valid_until' => $data['valid_until'] ?? null,
        ]);

        app(ActivityLoggerService::class)->log(
            description: 'Secret promotion updated',
            subject: $promo,
            causer: $admin,
            event: 'updated',
        );

        return $promo->fresh();
    }

    // ── Approve (vendor-created → active) ────────────────────────────────────

    public function approve(MarketerSecretPromotion $promo, Admin $admin): MarketerSecretPromotion
    {
        if ($promo->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending promotions can be approved.',
            ]);
        }

        $promo->update([
            'status' => 'active',
            'approved_by_admin_id' => $admin->id,
        ]);

        app(ActivityLoggerService::class)->log(
            description: 'Secret promotion approved',
            subject: $promo,
            causer: $admin,
            event: 'updated',
        );

        return $promo->fresh();
    }

    // ── Reject (vendor-created → expired) ────────────────────────────────────

    public function reject(MarketerSecretPromotion $promo, Admin $admin): MarketerSecretPromotion
    {
        if ($promo->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Only pending promotions can be rejected.',
            ]);
        }

        $promo->update(['status' => 'expired']);

        app(ActivityLoggerService::class)->log(
            description: 'Secret promotion rejected',
            subject: $promo,
            causer: $admin,
            event: 'updated',
        );

        return $promo->fresh();
    }

    // ── Pause ─────────────────────────────────────────────────────────────────

    public function pause(MarketerSecretPromotion $promo, Admin $admin): MarketerSecretPromotion
    {
        if ($promo->status !== 'active') {
            throw ValidationException::withMessages([
                'status' => 'Only active promotions can be paused.',
            ]);
        }

        $promo->update(['status' => 'paused']);

        app(ActivityLoggerService::class)->log(
            description: 'Secret promotion paused',
            subject: $promo,
            causer: $admin,
            event: 'updated',
        );

        return $promo->fresh();
    }

    // ── Resume ────────────────────────────────────────────────────────────────

    public function resume(MarketerSecretPromotion $promo, Admin $admin): MarketerSecretPromotion
    {
        if ($promo->status !== 'paused') {
            throw ValidationException::withMessages([
                'status' => 'Only paused promotions can be resumed.',
            ]);
        }

        if ($promo->isExpired()) {
            throw ValidationException::withMessages([
                'status' => 'Cannot resume an expired promotion. Duplicate it instead.',
            ]);
        }

        $promo->update(['status' => 'active']);

        app(ActivityLoggerService::class)->log(
            description: 'Secret promotion resumed',
            subject: $promo,
            causer: $admin,
            event: 'updated',
        );

        return $promo->fresh();
    }

    // ── Expire ────────────────────────────────────────────────────────────────

    public function expire(MarketerSecretPromotion $promo, Admin $admin): MarketerSecretPromotion
    {
        $promo->update([
            'status' => 'expired',
            'valid_until' => today(),
        ]);

        app(ActivityLoggerService::class)->log(
            description: 'Secret promotion manually expired',
            subject: $promo,
            causer: $admin,
            event: 'updated',
        );

        return $promo->fresh();
    }

    // ── Duplicate ─────────────────────────────────────────────────────────────

    public function duplicate(MarketerSecretPromotion $promo, Admin $admin): MarketerSecretPromotion
    {
        $new = MarketerSecretPromotion::create([
            'vendor_id' => $promo->vendor_id,
            'vendor_listing_id' => $promo->vendor_listing_id,
            'marketer_id' => $promo->marketer_id,
            'product_value_cents' => $promo->product_value_cents,
            'total_commission_pct' => $promo->total_commission_pct,
            'marketer_share_pct' => $promo->marketer_share_pct,
            'admin_share_pct' => $promo->admin_share_pct,
            'min_commission_pct' => $promo->min_commission_pct,
            'is_hidden_from_public' => 1,
            'status' => 'active',
            'valid_until' => null,
            'created_by_vendor' => 0,
            'approved_by_admin_id' => $admin->id,
        ]);

        app(ActivityLoggerService::class)->log(
            description: 'Secret promotion duplicated from ' . $promo->id,
            subject: $new,
            causer: $admin,
            event: 'created',
        );

        return $new;
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function getStatsForAdmin(): array
    {
        $totalActive = MarketerSecretPromotion::where('status', 'active')
            ->where(fn($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', today()))
            ->count();

        $totalPaused = MarketerSecretPromotion::where('status', 'paused')->count();

        $totalExpired = MarketerSecretPromotion::where(
            fn($q) =>
            $q->where('status', 'expired')->orWhere('valid_until', '<', today())
        )->count();

        // Admin revenue this month: sum across all conversions this month
        // secret_promotion_id lives on marketer_campaigns, not marketer_conversions,
        // so we filter through the campaign relationship.
        $monthStart = Carbon::now()->startOfMonth();
        $conversionsThisMonth = MarketerConversion::where('created_at', '>=', $monthStart)
            ->where('status', '!=', 'reversed')
            ->whereHas('campaign', fn($q) => $q->whereNotNull('secret_promotion_id'))
            ->with('campaign.secretPromotion')
            ->get();

        $adminRevenueMonth = $conversionsThisMonth->sum(function ($c) {
            $adminPct = $c->campaign?->secretPromotion?->admin_share_pct ?? 0;

            return (int) round($c->order_value_cents * $adminPct / 100);
        });

        $avgAdminShare = MarketerSecretPromotion::where('status', 'active')
            ->where(fn($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', today()))
            ->avg('admin_share_pct') ?? 0;

        $totalConversionsMonth = MarketerConversion::where('created_at', '>=', $monthStart)
            ->whereHas('campaign', fn($q) => $q->whereNotNull('secret_promotion_id'))
            ->count();

        $topPerforming = MarketerSecretPromotion::withCount('conversions')
            ->orderByDesc('conversions_count')
            ->limit(5)
            ->get();

        return [
            'total_active' => $totalActive,
            'total_paused' => $totalPaused,
            'total_expired' => $totalExpired,
            'admin_revenue_month' => $adminRevenueMonth,
            'avg_admin_share' => round($avgAdminShare, 1),
            'total_conversions_month' => $totalConversionsMonth,
            'top_performing' => $topPerforming,
        ];
    }

    // ── Scheduled: expire old promotions ──────────────────────────────────────

    public function expireOldPromotions(): int
    {
        return MarketerSecretPromotion::where('status', 'active')
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', today())
            ->update(['status' => 'expired']);
    }

    // ── For Marketer Portal (visibility-restricted) ───────────────────────────

    public function getForMarketer(Marketer $marketer): \Illuminate\Support\Collection
    {
        return MarketerSecretPromotion::scopeForMarketer(
            MarketerSecretPromotion::active(),
            $marketer->id
        )->get(['id', 'marketer_share_pct', 'status']);
    }

    // ── Private validation helpers ────────────────────────────────────────────

    private function validateCommissionSplit(array $data): void
    {
        $total = (float) ($data['total_commission_pct'] ?? 0);
        $marketer = (float) ($data['marketer_share_pct'] ?? 0);
        $admin = round($total - $marketer, 2);
        $minFloor = (float) setting('min_marketer_commission', 5.0);

        if ($admin < 0) {
            throw ValidationException::withMessages([
                'marketer_share_pct' => 'Admin share cannot be negative. Reduce the marketer share.',
            ]);
        }

        if ($marketer <= 0) {
            throw ValidationException::withMessages([
                'marketer_share_pct' => 'Marketer must receive at least some commission.',
            ]);
        }

        if ($total < $minFloor) {
            throw ValidationException::withMessages([
                'total_commission_pct' => "Total commission cannot be below the platform minimum of {$minFloor}%.",
            ]);
        }
    }

    private function validateListing(string $listingId, ?string $vendorId = null): void
    {
        $listing = VendorListing::find($listingId);

        if (!$listing) {
            throw ValidationException::withMessages([
                'vendor_listing_id' => 'Listing not found.',
            ]);
        }

        if ($listing->status !== 'active') {
            throw ValidationException::withMessages([
                'vendor_listing_id' => 'Cannot create secret promotion for an inactive listing.',
            ]);
        }

        if ($vendorId && $listing->vendor_id !== $vendorId) {
            throw ValidationException::withMessages([
                'vendor_listing_id' => 'This listing does not belong to the selected vendor.',
            ]);
        }
    }

    private function validateNoDuplicate(string $listingId, ?string $marketerId, ?string $excludeId = null): void
    {
        $query = MarketerSecretPromotion::active()
            ->where('vendor_listing_id', $listingId)
            ->where(fn($q) => $marketerId
                ? $q->where('marketer_id', $marketerId)
                : $q->whereNull('marketer_id'));

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'vendor_listing_id' => 'An active promotion already exists for this listing and marketer.',
            ]);
        }
    }
}
