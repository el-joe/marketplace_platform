<?php

namespace App\Services;

use App\Models\Marketer;
use App\Models\MarketerCampaign;
use App\Models\MarketerQrCode;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class QrCodeGenerationService
{
    /**
     * Generate a QR code image, persist it, and return the MarketerQrCode record.
     * Ownership validation (campaign/listing belongs to marketer) must be done
     * by the caller before invoking this method.
     */
    public function generate(
        Marketer $marketer,
        string $codeType,
        string $targetUrl,
        ?string $label = null,
        ?string $campaignId = null,
        ?string $vendorListingId = null,
        ?string $productId = null,
    ): MarketerQrCode {
        $qr = QrCode::create($targetUrl)
            ->setSize(300)
            ->setMargin(10)
            ->setEncoding(new Encoding('UTF-8'));

        $result = (new PngWriter())->write($qr);

        $path = 'marketer-qr/' . $marketer->id . '/' . uniqid('qr_', true) . '.png';
        Storage::put($path, $result->getString());

        return MarketerQrCode::create([
            'marketer_id'      => $marketer->id,
            'campaign_id'      => $campaignId,
            'vendor_listing_id'=> $vendorListingId,
            'product_id'       => $productId,
            'code_type'        => $codeType,
            'qr_code_path'     => $path,
            'barcode_value'    => $targetUrl,
            'custom_label'     => $label,
            'scan_count'       => 0,
            'is_active'        => true,
        ]);
    }
}
