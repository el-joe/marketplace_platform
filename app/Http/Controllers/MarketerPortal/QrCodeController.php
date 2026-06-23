<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerQrCode;
use App\Services\MarketerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QrCodeController extends Controller
{
    public function __construct(private readonly MarketerService $service)
    {
    }

    public function index(): View
    {
        $marketer = Auth::guard('marketer')->user();

        $qrCodes = $marketer->qrCodes()
            ->with('campaign')
            ->orderByDesc('created_at')
            ->paginate(24);

        return view('marketer.qr-codes.index', [
            'marketer' => $marketer,
            'qrCodes' => $qrCodes,
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code_type' => 'required|in:marketer_profile,product,vendor_store,campaign,whatsapp_link',
            'target_url' => 'required|url|max:500',
            'custom_label' => 'nullable|string|max:150',
            'campaign_id' => 'nullable|uuid|exists:marketer_campaigns,id',
            'vendor_listing_id' => 'nullable|uuid|exists:vendor_listings,id',
        ]);

        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $qrCode = $this->service->generateQrCode(
            $marketer,
            $validated['code_type'],
            $validated['target_url'],
            $validated['custom_label'] ?? null,
            $validated['campaign_id'] ?? null,
            $validated['vendor_listing_id'] ?? null,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $qrCode->id,
                'qr_url' => $qrCode->qr_url,
                'download_url' => route('marketer.qr-codes.download', $qrCode),
                'barcode_value' => $qrCode->barcode_value,
            ],
        ]);
    }

    public function download(MarketerQrCode $qrCode): Response
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($qrCode->marketer_id !== $marketer->id, 403);
        abort_if(!$qrCode->qr_code_path || !Storage::exists($qrCode->qr_code_path), 404);

        $filename = 'qr-' . ($qrCode->custom_label ? \Illuminate\Support\Str::slug($qrCode->custom_label) : $qrCode->id) . '.png';

        return response(Storage::get($qrCode->qr_code_path), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
