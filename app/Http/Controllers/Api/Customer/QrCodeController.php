<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CustomerQrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class QrCodeController extends Controller
{
    public function __construct(private readonly CustomerQrCodeService $service)
    {
    }

    public function show(): JsonResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if (!$customer->qr_code_path || !Storage::disk('public')->exists($customer->qr_code_path)) {
            $this->service->generate($customer);
        }

        return response()->json($this->payload($customer));
    }

    public function regenerate(): JsonResponse
    {
        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();

        if ($customer->qr_code_path) {
            Storage::disk('public')->delete($customer->qr_code_path);
        }

        $customer->qr_code_path = null;
        $customer->save();

        $this->service->generate($customer);

        return response()->json($this->payload($customer));
    }

    private function payload(Customer $customer): array
    {
        return [
            'qr_url' => Storage::disk('public')->url($customer->qr_code_path),
            'referral_code' => $customer->referral_code,
            'referral_link' => rtrim(config('app.url'), '/') . '/r/' . $customer->referral_code,
        ];
    }
}
