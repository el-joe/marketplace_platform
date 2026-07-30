<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminPromotionSettingsController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $settings = Setting::where('category', 'promotion')->get()->keyBy('key');

        return view('admin.settings.promotion.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'promotion_fixed_admin_commission' => 'required|integer|min:0',
            'promotion_fee_per_celebrity' => 'required|integer|min:0',
            'promotion_request_acceptance_window_hours' => 'required|integer|min:1|max:168',
            'promotion_min_stock_multiplier' => 'required|integer|min:1',
            'promotion_tier1_monthly_minimum' => 'required|integer|min:0',
            'promotion_tier2_monthly_minimum' => 'required|integer|min:0',
            'promotion_tier3_monthly_minimum' => 'required|integer|min:0',
            'promotion_tier4_monthly_minimum' => 'required|integer|min:0',
        ]);

        foreach ($validated as $key => $value) {
            Setting::where('key', $key)->update([
                'value' => json_encode($value),
                'updated_by_admin_id' => auth('admin')->id(),
            ]);
            Cache::forget('setting:' . $key);
        }

        Cache::forget('settings:promotion');

        return redirect()->route('admin.settings.promotion.index')
            ->with('success', __('admin.settings_section.settings_saved'));
    }
}
