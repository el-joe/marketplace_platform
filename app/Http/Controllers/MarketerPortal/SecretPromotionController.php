<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\MarketerSecretPromotion;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SecretPromotionController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $promotions = MarketerSecretPromotion::query()
            ->forMarketer($marketer->id)
            ->whereIn('status', ['active', 'pending'])
            ->with(['vendorListing.productVariant.product', 'vendor'])
            ->orderByDesc('created_at')
            ->get();

        return view('marketer.secret_promotions.index', [
            'marketer' => $marketer,
            'promotions' => $promotions,
        ]);
    }
}
