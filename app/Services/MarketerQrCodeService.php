<?php

namespace App\Services;

use App\Enums\MarketerQrCodeType;
use App\Models\MarketerQrCode;
use App\Models\VendorInfluencerPromotionRequestItem;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class MarketerQrCodeService
{
    public function generateForPromotionItem(VendorInfluencerPromotionRequestItem $item, string $campaignId): MarketerQrCode
    {
        $qrCode = MarketerQrCode::query()->create([
            'marketer_id' => $item->marketer_id,
            'campaign_id' => $campaignId,
            'vendor_listing_id' => $item->promotionRequest->vendor_listing_id,
            'code_type' => MarketerQrCodeType::Product,
        ]);

        $trackingUrl = rtrim(config('app.url'), '/') . '/qr/' . $qrCode->id;
        $qr = new QrCode(
            data: $trackingUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 400,
            margin: 20,
        );
        $result = (new PngWriter())->write($qr);
        $path = 'marketer-qr/' . $qrCode->id . '.png';
        Storage::disk('public')->put($path, $result->getString());

        $qrCode->qr_code_path = Storage::disk('public')->url($path);
        $qrCode->save();

        return $qrCode;
    }
}
