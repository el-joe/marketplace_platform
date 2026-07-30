<?php

namespace App\Services;

use App\Enums\CampaignType;
use App\Enums\InventoryMovementReferenceType;
use App\Enums\InventoryMovementType;
use App\Enums\MarketerSampleRequestStatus;
use App\Enums\MarketerStatus;
use App\Jobs\AutoReassignInfluencerJob;
use App\Jobs\SendInfluencerPromotionWhatsAppNotificationJob;
use App\Jobs\SendVendorFulfillmentAlertJob;
use App\Models\Admin as AdminModel;
use App\Models\AdminProductListing;
use App\Models\InventoryMovement;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerMonthlyQuotaProgress;
use App\Models\MarketerQrCode;
use App\Models\MarketerSampleItem;
use App\Models\MarketerSampleRequest;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorInfluencerPromotionRequest;
use App\Models\VendorInfluencerPromotionRequestItem;
use App\Models\VendorInfluencerReassignmentLog;
use App\Models\VendorListing;
use App\Models\Wallet;
use App\Notifications\Admin\InfluencerReassignmentLogged;
use App\Notifications\Marketer\NewPromotionRequestReceived;
use App\Notifications\Marketer\PromotionRequestReassignedAway;
use App\Notifications\Vendor\PromotionRequestCancelled;
use App\Notifications\Vendor\PromotionRequestReassigned;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InfluencerPromotionRequestService
{
    public function __construct(
        private readonly InfluencerPromotionFeeService $feeService,
        private readonly MarketerQrCodeService $qrCodeService,
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * Cost breakdown for the vendor to preview before submitting a request.
     * BIGINT base-currency units. Never divide or multiply by 100.
     */
    public function calculatePromotionCost(int $numCelebrities, string $fulfillmentModel, Vendor $vendor): array
    {
        $countrySettings = DB::table('promotion_country_settings')
            ->where('country_id', $vendor->country_id)
            ->first();

        $fixedAdminCommission = $countrySettings->admin_commission ?? 2;
        $feePerCelebrity = $countrySettings->fee_per_celebrity ?? 9;
        $variableCommissionRate = (float) Setting::get(
            "promotion_variable_commission_{$fulfillmentModel}",
            5.0
        );

        return [
            'fixed_admin_commission' => $fixedAdminCommission,
            'promotion_fees' => $feePerCelebrity * $numCelebrities,
            'variable_commission_note' => "Variable commission applied per sale ({$variableCommissionRate}%)",
            'total_upfront_cost' => $fixedAdminCommission + ($feePerCelebrity * $numCelebrities),
            'currency' => $vendor->country?->currency_code ?? 'SAR',
            'samples_required' => $numCelebrities + 1, // N celebrities + 1 admin
        ];
    }

    public function createRequest(array $data, Vendor $vendor): VendorInfluencerPromotionRequest
    {
        return DB::transaction(function () use ($data, $vendor) {
            $hasVendorListing = ! empty($data['vendor_listing_id']);
            $hasAdminListing = ! empty($data['admin_product_listing_id']);

            if ($hasVendorListing === $hasAdminListing) {
                throw new \InvalidArgumentException('Exactly one of vendor_listing_id or admin_product_listing_id must be provided.');
            }

            $listing = $hasVendorListing
                ? VendorListing::query()->findOrFail($data['vendor_listing_id'])
                : AdminProductListing::query()->findOrFail($data['admin_product_listing_id']);

            $this->feeService->validateStockRequirement($listing);

            if ($hasVendorListing) {
                $listingFulfillmentModel = $listing->fulfillment_model;
                $requiresWarehouseReceipt = $listing->fulfillment_model === 'fbm'
                    || $listing->fulfillment_model === 'cross_dock'
                    || $listing->global_system_type === 'merchant_fbp';
            } else {
                $listingFulfillmentModel = $listing->fulfillment_type === 'global' ? 'admin_global' : 'admin_express';
                $requiresWarehouseReceipt = false;
            }

            $feePerSlot = $this->feeService->getPromotionFeePerSlot($listing);
            $marketerIds = $data['marketer_ids'];
            $feeDeductionTiming = $data['fee_deduction_timing'] ?? 'weekly_settlement';
            $isImmediate = $feeDeductionTiming === 'immediate';

            $request = VendorInfluencerPromotionRequest::query()->create([
                'vendor_id' => $vendor->id,
                'vendor_listing_id' => $hasVendorListing ? $listing->id : null,
                'admin_product_listing_id' => $hasAdminListing ? $listing->id : null,
                'status' => 'pending',
                'listing_fulfillment_model' => $listingFulfillmentModel,
                'requires_warehouse_receipt' => $requiresWarehouseReceipt,
                'total_promotion_fee' => $feePerSlot * count($marketerIds),
                'currency' => $listing->currency,
                'fee_deduction_timing' => $feeDeductionTiming,
                'fee_deducted' => $isImmediate,
                'fee_deducted_at' => $isImmediate ? now() : null,
                'vendor_note' => $data['vendor_note'] ?? null,
                'created_by_admin_id' => $data['created_by_admin_id'] ?? null,
            ]);

            foreach ($marketerIds as $marketerId) {
                $item = VendorInfluencerPromotionRequestItem::query()->create([
                    'promotion_request_id' => $request->id,
                    'marketer_id' => $marketerId,
                    'status' => 'pending',
                    'slot_promotion_fee' => $feePerSlot,
                ]);

                $item->update(['expires_at' => now()->addHours($item->acceptance_window_hours)]);

                SendInfluencerPromotionWhatsAppNotificationJob::dispatch($item);
            }

            if ($requiresWarehouseReceipt) {
                SendVendorFulfillmentAlertJob::dispatch($request);
            }

            return $request;
        });
    }

    public function handleInfluencerResponse(VendorInfluencerPromotionRequestItem $item, string $action, ?string $note): void
    {
        DB::transaction(function () use ($item, $action, $note) {
            if ($action === 'accept') {
                $item->update([
                    'status' => 'accepted',
                    'responded_at' => now(),
                    'marketer_note' => $note,
                ]);

                $promotionRequest = $item->promotionRequest;

                $campaign = MarketerCampaign::query()->create([
                    'marketer_id' => $item->marketer_id,
                    'vendor_id' => $promotionRequest->vendor_id,
                    'campaign_type' => CampaignType::ProductPromotion,
                    'status' => 'active',
                    'campaignable_type' => Vendor::class,
                    'campaignable_id' => $promotionRequest->vendor_id,
                ]);

                $item->update(['resulting_campaign_id' => $campaign->id]);

                $qrCode = $this->qrCodeService->generateForPromotionItem($item, $campaign->id);
                $item->update(['qr_code_id' => $qrCode->id]);

                $this->deductSamples($item);
                $this->deductPromotionFees($item);

                $period = now();
                MarketerMonthlyQuotaProgress::query()
                    ->forPeriod($period->year, $period->month)
                    ->forCategory(1)
                    ->where('marketer_id', $item->marketer_id)
                    ->increment('completed_count');
            } else {
                $item->update([
                    'status' => 'declined',
                    'responded_at' => now(),
                    'decline_reason' => $note,
                ]);

                AutoReassignInfluencerJob::dispatch($item->id);
            }
        });
    }

    /**
     * Deducts 1 sample for the accepting celebrity + 1 admin sample from the
     * vendor's warehouse stock. Samples are recorded at price = 0; the admin
     * sample's cost is a debt the platform owes the vendor (settled separately).
     * No-op for admin-fulfilled listings, which hold no vendor-owned stock.
     */
    public function deductSamples(VendorInfluencerPromotionRequestItem $item): void
    {
        $listing = $item->promotionRequest->vendorListing;

        if ($listing === null) {
            return;
        }

        $inventory = $listing->warehouseInventories()->orderByDesc('quantity_on_hand')->first();

        if ($inventory === null) {
            return;
        }

        $sampleRequest = MarketerSampleRequest::query()->create([
            'marketer_id' => $item->marketer_id,
            'vendor_id' => $item->promotionRequest->vendor_id,
            'status' => MarketerSampleRequestStatus::Approved,
            'notes' => 'Auto-generated for influencer promotion acceptance',
        ]);

        MarketerSampleItem::query()->create([
            'sample_request_id' => $sampleRequest->id,
            'vendor_listing_id' => $listing->id,
            'quantity' => 2,
            'marketer_quantity' => 1,
            'admin_quantity' => 1,
            'is_mandatory' => true,
            'sample_cost' => 0,
            'created_at' => now(),
        ]);

        foreach (['Celebrity Sample - ' . $item->marketer_id, 'Admin Sample'] as $label) {
            $inventory->decrement('quantity_on_hand');

            InventoryMovement::query()->create([
                'warehouse_inventory_id' => $inventory->id,
                'movement_type' => InventoryMovementType::Outbound,
                'quantity_delta' => -1,
                'quantity_after' => $inventory->quantity_on_hand,
                'reference_type' => InventoryMovementReferenceType::Adjustment,
                'reference_id' => $sampleRequest->id,
                'reason' => $label,
            ]);
        }
    }

    /**
     * Deducts the celebrity slot fee + pro-rata share of the fixed admin
     * commission from the vendor's wallet, recording a double-entry ledger
     * movement (debit vendor, credit platform). BIGINT base-currency units.
     */
    public function deductPromotionFees(VendorInfluencerPromotionRequestItem $item): void
    {
        $request = $item->promotionRequest;

        DB::transaction(function () use ($item, $request) {
            $vendorWallet = Wallet::query()
                ->where('owner_type', \App\Enums\WalletOwnerType::Vendor)
                ->where('owner_id', $request->vendor_id)
                ->where('currency', $request->currency)
                ->lockForUpdate()
                ->first();

            if (! $vendorWallet || $vendorWallet->is_frozen) {
                throw new \Exception('Vendor wallet unavailable for fee deduction.');
            }

            $numSlots = $request->num_celebrities_requested ?: 1;
            $adminShare = (int) floor($request->fixed_admin_commission_snapshot / $numSlots);
            $slotFee = (int) $item->slot_promotion_fee;
            $totalDebit = $slotFee + $adminShare;

            // Wallet::balance is BIGINT in the DB but the model accessor exposes it
            // divided by 100; read/write the raw column to stay in base-currency units.
            $rawBalance = (int) $vendorWallet->getRawOriginal('balance');

            if ($rawBalance < $totalDebit) {
                throw new \Exception('Insufficient vendor wallet balance for promotion fee deduction.');
            }

            Wallet::query()->whereKey($vendorWallet->id)->decrement('balance', $totalDebit);

            $this->ledgerService->record($this->ledgerService->newGroupId(), [
                [
                    'account_type' => 'seller_payable',
                    'account_holder_type' => Vendor::class,
                    'account_holder_id' => $request->vendor_id,
                    'debit' => $totalDebit,
                    'credit' => 0,
                    'currency' => $request->currency,
                    'reference_type' => VendorInfluencerPromotionRequestItem::class,
                    'reference_id' => $item->id,
                    'description' => 'Promotion fee: celebrity slot + admin commission',
                ],
                [
                    'account_type' => 'platform_revenue',
                    'account_holder_type' => null,
                    'account_holder_id' => null,
                    'debit' => 0,
                    'credit' => $totalDebit,
                    'currency' => $request->currency,
                    'reference_type' => VendorInfluencerPromotionRequestItem::class,
                    'reference_id' => $item->id,
                    'description' => 'Promotion fee received from vendor',
                ],
            ]);

            $allDeducted = VendorInfluencerPromotionRequestItem::query()
                ->where('promotion_request_id', $request->id)
                ->where('status', 'accepted')
                ->count() === $request->num_celebrities_requested;

            if ($allDeducted) {
                $request->update(['fee_deducted' => true, 'fee_deducted_at' => now()]);
            }
        });
    }

    public function autoReassign(VendorInfluencerPromotionRequestItem $item, ?string $triggeredByAdminId = null, ?string $reason = null): void
    {
        DB::transaction(function () use ($item, $triggeredByAdminId, $reason) {
            $excludedMarketerIds = VendorInfluencerPromotionRequestItem::query()
                ->where('promotion_request_id', $item->promotion_request_id)
                ->pluck('marketer_id');

            $candidate = Marketer::query()
                ->where('status', MarketerStatus::Active)
                ->where('accept_new_campaigns', true)
                ->whereNotIn('id', $excludedMarketerIds)
                ->withCount(['influencerPromotionRequestItems' => function ($query) {
                    $query->where('created_at', '>=', now()->subDays(30));
                }])
                ->orderBy('influencer_promotion_request_items_count')
                ->first();

            if (! $candidate) {
                $item->promotionRequest->update(['status' => 'cancelled']);

                Notification::send($item->promotionRequest->vendor->vendorAdmins, new PromotionRequestCancelled($item->promotionRequest));

                return;
            }

            $newItem = VendorInfluencerPromotionRequestItem::query()->create([
                'promotion_request_id' => $item->promotion_request_id,
                'marketer_id' => $candidate->id,
                'status' => 'pending',
                'slot_promotion_fee' => $item->slot_promotion_fee,
            ]);

            $newItem->update(['expires_at' => now()->addHours($newItem->acceptance_window_hours)]);

            if ($item->qr_code_id) {
                $newItem->update(['qr_code_id' => $item->qr_code_id]);
            }

            $qrCode = MarketerQrCode::query()
                ->where('promotion_request_item_id', $item->id)
                ->where('is_active', true)
                ->first();

            if ($qrCode) {
                $qrCode->previous_marketer_id = $item->marketer_id;
                $qrCode->marketer_id = $candidate->id;
                $qrCode->promotion_request_item_id = $newItem->id;
                $qrCode->reassigned_at = now();
                $this->qrCodeService->regenerateForReassignment($qrCode);
                $qrCode->save();
            }

            $log = VendorInfluencerReassignmentLog::query()->create([
                'promotion_request_id' => $item->promotion_request_id,
                'promotion_request_item_id' => $item->id,
                'from_marketer_id' => $item->marketer_id,
                'to_marketer_id' => $candidate->id,
                'reason' => $reason ?? ($item->status === 'declined' ? 'declined' : 'timed_out'),
                'to_marketer_request_count_30d' => $candidate->influencer_promotion_request_items_count,
                'triggered_by_admin_id' => $triggeredByAdminId,
            ]);

            Notification::send($item->marketer, new PromotionRequestReassignedAway($item));
            Notification::send($candidate, new NewPromotionRequestReceived($newItem));
            Notification::send($item->promotionRequest->vendor->vendorAdmins, new PromotionRequestReassigned($newItem));
            Notification::send(
                AdminModel::permission('influencer_promotions.view')->get(),
                new InfluencerReassignmentLogged($log)
            );
        });
    }
}
