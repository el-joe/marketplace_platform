<?php

namespace App\Services;

use App\Enums\CampaignType;
use App\Enums\MarketerStatus;
use App\Jobs\AutoReassignInfluencerJob;
use App\Jobs\SendInfluencerPromotionWhatsAppNotificationJob;
use App\Jobs\SendVendorFulfillmentAlertJob;
use App\Models\Admin as AdminModel;
use App\Models\AdminProductListing;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerMonthlyQuotaProgress;
use App\Models\Vendor;
use App\Models\VendorInfluencerPromotionRequest;
use App\Models\VendorInfluencerPromotionRequestItem;
use App\Models\VendorInfluencerReassignmentLog;
use App\Models\VendorListing;
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
    ) {}

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

    public function autoReassign(VendorInfluencerPromotionRequestItem $item): void
    {
        DB::transaction(function () use ($item) {
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

            $log = VendorInfluencerReassignmentLog::query()->create([
                'promotion_request_id' => $item->promotion_request_id,
                'promotion_request_item_id' => $item->id,
                'from_marketer_id' => $item->marketer_id,
                'to_marketer_id' => $candidate->id,
                'reason' => $item->status === 'declined' ? 'declined' : 'timed_out',
                'to_marketer_request_count_30d' => $candidate->influencer_promotion_request_items_count,
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
