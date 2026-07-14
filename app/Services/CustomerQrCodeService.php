<?php

namespace App\Services;

use App\Models\Customer;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class CustomerQrCodeService
{
    public function generate(Customer $customer): string
    {
        $referralLink = rtrim(config('app.url'), '/') . '/r/' . $customer->referral_code;

        $qr = QrCode::create($referralLink)
            ->setSize(400)
            ->setMargin(20)
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setEncoding(new Encoding('UTF-8'));

        $result = (new PngWriter())->write($qr);

        $path = 'customer-qr/' . $customer->id . '.png';
        Storage::disk('public')->put($path, $result->getString());

        $customer->qr_code_path = $path;
        $customer->save();

        return Storage::disk('public')->url($path);
    }
}
