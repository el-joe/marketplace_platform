<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePromoCode;
use App\Models\Coupon;
use App\Models\Marketer;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AffiliatePromoCodeController extends Controller
{
    public function index(): View
    {
        /** @var Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $promoCodes = $marketer->affiliatePromoCodes()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('marketer.promo_codes.index', [
            'marketer' => $marketer,
            'promoCodes' => $promoCodes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $validated = $request->validate([
            'code' => 'nullable|string|max:50|regex:/^[A-Z0-9\-]+$/',
            'discount_type' => 'required|in:percentage,fixed_amount,free_shipping',
            'discount_value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'max_uses' => 'nullable|integer|min:1',
            'min_order_amount' => 'nullable|integer|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
        ]);

        if ($validated['discount_type'] === 'fixed_amount' && empty($validated['currency'])) {
            return back()->withErrors(['currency' => __('marketer.promo_codes.currency_required')])->withInput();
        }

        $code = $validated['code'] ?? null;

        if ($code) {
            $exists = AffiliatePromoCode::where('code', $code)->exists()
                || Coupon::where('code', $code)->exists();

            if ($exists) {
                return back()->withErrors(['code' => __('marketer.promo_codes.code_taken')])->withInput();
            }
        }

        // VERIFY: system_settings key controlling whether self-serve affiliate
        // codes activate immediately or require admin review. Defaults to
        // requiring review (is_active = 0) when the setting is absent.
        $autoActivate = (bool) Setting::get('affiliate_promo_codes_auto_activate', false);

        $marketer->affiliatePromoCodes()->create([
            ...$validated,
            'code' => $code ?? strtoupper('AFF-' . \Illuminate\Support\Str::random(8)),
            'is_active' => $autoActivate,
        ]);

        return redirect()
            ->route('marketer.promo-codes.index')
            ->with('success', $autoActivate
                ? __('marketer.promo_codes.created_active')
                : __('marketer.promo_codes.created_pending'));
    }

    public function show(string $id): View
    {
        /** @var Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $promoCode = $marketer->affiliatePromoCodes()->findOrFail($id);

        $conversions = $promoCode->conversions()
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('marketer.promo_codes.show', [
            'marketer' => $marketer,
            'promoCode' => $promoCode,
            'conversions' => $conversions,
        ]);
    }
}
