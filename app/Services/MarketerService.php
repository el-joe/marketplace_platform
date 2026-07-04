<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerCommissionTier;
use App\Models\MarketerConversion;
use App\Models\MarketerQrCode;
use App\Models\MarketerSampleItem;
use App\Models\MarketerSampleRequest;
use App\Models\MarketerSecretPromotion;
use App\Models\MarketerWhatsappLink;
use App\Models\Order;
use App\Models\Vendor;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarketerService
{
    // ─── Resolve Commission Rate ──────────────────────────────────────────────

    public function resolveCommissionRate(
        Marketer $marketer,
        MarketerCampaign $campaign,
        ?MarketerSecretPromotion $secret = null
    ): float {
        // If secret promotion set, marketer always gets their fixed share
        if ($secret && $secret->isActive()) {
            return (float) $secret->marketer_share_pct;
        }

        // Count non-reversed conversions for tiered rate
        $salesCount = MarketerConversion::where('marketer_id', $marketer->id)
            ->where('campaign_id', $campaign->id)
            ->where('status', '!=', 'reversed')
            ->count();

        // Check campaign-specific tiers first, then marketer global tiers
        $tier = MarketerCommissionTier::where('marketer_id', $marketer->id)
            ->where(
                fn($q) => $q
                    ->where('campaign_id', $campaign->id)
                    ->orWhereNull('campaign_id')
            )
            ->active()
            ->forSalesCount($salesCount)
            ->first();

        return (float) ($tier?->commission_rate
            ?? $campaign->commission_rate
            ?? $marketer->commission_rate
            ?? 0);
    }

    // ─── Generate QR Code ─────────────────────────────────────────────────────

    public function generateQrCode(
        Marketer $marketer,
        string $type,
        string $targetUrl,
        ?string $label = null,
        ?string $campaignId = null,
        ?string $vendorListingId = null
    ): MarketerQrCode {
        $qr = new QrCode(
            data: $targetUrl,
            size: 300,
            margin: 10,
            encoding: new Encoding('UTF-8')
        );
        $writer = new PngWriter();
        $result = $writer->write($qr);


        // make sure the directory exists
        Storage::disk('public')->makeDirectory('marketer-qr/' . $marketer->id);

        $name = 'storage/marketer-qr/' . $marketer->id . '/' . $campaignId . '.png';

        $result->saveToFile(public_path($name));

        return MarketerQrCode::create([
            'marketer_id' => $marketer->id,
            'campaign_id' => $campaignId,
            'vendor_listing_id' => $vendorListingId,
            'code_type' => $type,
            'qr_code_path' => asset($name),
            'barcode_value' => $targetUrl,
            'custom_label' => $label,
            'scan_count' => 0,
            'is_active' => 1,
        ]);
    }

    // ─── Create WhatsApp Link ─────────────────────────────────────────────────

    public function createWhatsappLink(
        Marketer $marketer,
        MarketerCampaign $campaign,
        string $linkType,
        ?float $discountPct = null,
        bool $freeShipping = false
    ): MarketerWhatsappLink {
        $couponCode = null;

        if ($discountPct || $freeShipping) {
            $coupon = Coupon::create([
                'code' => 'WA-' . strtoupper(Str::random(8)),
                'name' => 'WhatsApp link — ' . $marketer->name,
                'type' => $discountPct ? 'percentage' : 'free_shipping',
                'value' => $discountPct ?? 100,
                'scope' => 'platform',
                'usage_limit_total' => null,
                'usage_limit_per_customer' => 1,
                'is_stackable' => 0,
                'valid_from' => now(),
                'valid_until' => now()->addDays(30),
            ]);
            $couponCode = $coupon->code;
        }

        $trackingUrl = url('/r/' . $campaign->tracking_url_slug
            . '?ref=' . $marketer->referral_code
            . '&via=wa'
            . ($couponCode ? '&coupon=' . $couponCode : ''));

        $template = $marketer->name . ' يشاركك عرضاً حصرياً 🎁'
            . ($discountPct ? " خصم {$discountPct}%!" : '')
            . ($freeShipping ? ' شحن مجاني!' : '')
            . "\n\n{{link}}";

        $qr = $this->generateQrCode(
            $marketer,
            'whatsapp_link',
            $trackingUrl,
            null,
            $campaign->id
        );

        return MarketerWhatsappLink::create([
            'marketer_id' => $marketer->id,
            'campaign_id' => $campaign->id,
            'link_type' => $linkType,
            'discount_pct' => $discountPct,
            'free_shipping' => $freeShipping ? 1 : 0,
            'coupon_code' => $couponCode,
            'whatsapp_message_template' => $template,
            'tracking_url' => $trackingUrl,
            'qr_code_path' => $qr->qr_code_path,
            'is_active' => 1,
        ]);
    }

    // ─── Request Samples ──────────────────────────────────────────────────────

    public function requestSamples(
        Marketer $marketer,
        Vendor $vendor,
        MarketerCampaign $campaign,
        array $items
    ): MarketerSampleRequest {
        $newCount = collect($items)->sum('quantity');

        if (
            $marketer->total_samples_allocated > 0
            && ($marketer->total_samples_used + $newCount) > $marketer->total_samples_allocated
        ) {
            throw ValidationException::withMessages([
                'quantity' => 'Exceeds your allocated sample limit ('
                    . $marketer->total_samples_allocated . ' total).',
            ]);
        }

        return DB::transaction(function () use ($marketer, $vendor, $campaign, $items) {
            $request = MarketerSampleRequest::create([
                'marketer_id' => $marketer->id,
                'vendor_id' => $vendor->id,
                'campaign_id' => $campaign->id,
                'status' => 'requested',
            ]);

            foreach ($items as $item) {
                $qty = (int) $item['quantity'];
                MarketerSampleItem::create([
                    'sample_request_id' => $request->id,
                    'vendor_listing_id' => $item['listing_id'],
                    'quantity'          => $qty,
                    'marketer_quantity' => $qty,
                    'admin_quantity'    => 0,
                    'is_mandatory'      => false,
                    'sample_cost_cents' => $item['cost_cents'] ?? 0,
                    'created_at'        => now(),
                ]);
            }

            return $request;
        });
    }

    // ─── Record Conversion ────────────────────────────────────────────────────

    public function recordConversion(Order $order): array
    {
        $attribution = session('marketer_attribution');

        if (!$attribution || now()->isAfter($attribution['expires_at'] ?? now()->subDay())) {
            return [];
        }

        $campaign = MarketerCampaign::find($attribution['campaign_id'] ?? null);
        if (!$campaign) {
            return [];
        }

        $marketer = Marketer::find($attribution['marketer_id'] ?? null);
        if (!$marketer) {
            return [];
        }

        $secret = $campaign->secretPromotion?->isActive() ? $campaign->secretPromotion : null;
        $rate = $this->resolveCommissionRate($marketer, $campaign, $secret);

        $orderTotal = $order->total_cents ?? ($order->total * 100);
        $commission = (int) round($orderTotal * $rate / 100);

        // Build click chain
        $clickIds = \App\Models\MarketerClick::where('customer_id', $order->customer_id)
            ->where('clicked_at', '<=', $order->created_at ?? now())
            ->where('clicked_at', '>=', now()->subDays(30))
            ->pluck('id')
            ->toArray();

        $lastClick = !empty($clickIds)
            ? \App\Models\MarketerClick::orderByDesc('clicked_at')->whereIn('id', $clickIds)->first()
            : null;

        return DB::transaction(function () use ($order, $marketer, $campaign, $secret, $rate, $commission, $clickIds, $lastClick, $attribution) {
            $conversion = \App\Models\MarketerConversion::create([
                'campaign_id' => $campaign->id,
                'marketer_id' => $marketer->id,
                'click_id' => $lastClick?->id,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'vendor_id' => $order->vendor_id ?? null,
                'order_value_cents' => $order->total_cents ?? (int) round($order->total * 100),
                'commission_rate' => $rate,
                'commission_amount_cents' => $commission,
                'currency' => $order->currency ?? 'EGP',
                'status' => 'pending',
                'attribution_model' => $campaign->attribution_model ?? 'last_click',
                'click_chain' => $clickIds,
                'is_whatsapp_conversion' => ($attribution['via_whatsapp'] ?? false) ? 1 : 0,
                'whatsapp_link_id' => $attribution['whatsapp_link_id'] ?? null,
            ]);

            $campaign->increment('total_conversions');
            $campaign->increment('total_revenue_cents', $order->total_cents ?? (int) round($order->total * 100));
            if ($campaign->budget_cents) {
                $campaign->increment('budget_spent_cents', $commission);
            }

            $marketer->increment('total_conversions');
            $marketer->increment('total_earnings_cents', $commission);

            if ($lastClick) {
                $lastClick->update(['converted' => 1, 'conversion_order_id' => $order->id]);
            }

            return [$conversion];
        });
    }
}
