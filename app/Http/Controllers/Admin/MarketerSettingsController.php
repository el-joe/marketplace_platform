<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Models\MarketerCommissionCountrySetting;
use App\Models\MarketerInfluencerFeeCountrySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketerSettingsController extends Controller
{
    public function index(): View
    {
        $this->authorize('marketer_commission_settings.view');

        $countries = Country::where('is_active', true)->orderBy('name_ar')->get();
        $categories = Category::orderBy('lft')->get();

        $commissions = MarketerCommissionCountrySetting::with(['category', 'country'])
            ->get()
            ->groupBy('country_id');

        $influencerFees = MarketerInfluencerFeeCountrySetting::with('country')
            ->get()
            ->keyBy('country_id');

        return view('admin.marketer_settings.index', compact(
            'countries', 'categories', 'commissions', 'influencerFees'
        ));
    }

    public function updateCommission(Request $request): RedirectResponse
    {
        $this->authorize('marketer_commission_settings.edit');

        $validated = $request->validate([
            'category_id' => 'required|uuid|exists:categories,id',
            'country_id' => 'required|uuid|exists:countries,id',
            'influencer_commission_amount' => 'required|integer|min:0',
            'affiliate_commission_amount' => 'required|integer|min:0',
            'currency' => 'required|string|size:3',
        ]);

        MarketerCommissionCountrySetting::updateOrCreate(
            [
                'category_id' => $validated['category_id'],
                'country_id' => $validated['country_id'],
            ],
            [
                'influencer_commission_amount' => $validated['influencer_commission_amount'],
                'affiliate_commission_amount' => $validated['affiliate_commission_amount'],
                'currency' => $validated['currency'],
                'updated_by_admin_id' => auth('admin')->id(),
            ]
        );

        return back()->with('success', 'تم حفظ إعدادات الكوميشن.');
    }

    public function updateInfluencerFee(Request $request): RedirectResponse
    {
        $this->authorize('marketer_fee_settings.edit');

        $validated = $request->validate([
            'country_id' => 'required|uuid|exists:countries,id',
            'fee_per_influencer' => 'required|integer|min:0',
            'currency' => 'required|string|size:3',
        ]);

        MarketerInfluencerFeeCountrySetting::updateOrCreate(
            ['country_id' => $validated['country_id']],
            [
                'fee_per_influencer' => $validated['fee_per_influencer'],
                'currency' => $validated['currency'],
                'updated_by_admin_id' => auth('admin')->id(),
            ]
        );

        return back()->with('success', 'تم حفظ رسوم الإنفلوينسر.');
    }

    public function updateCategorySettings(Request $request): RedirectResponse
    {
        $this->authorize('marketer_commission_settings.edit');

        $validated = $request->validate([
            'category_id' => 'required|uuid|exists:categories,id',
            'influencer_sample_qty' => 'required|integer|min:0',
            'affiliate_sample_qty' => 'required|integer|min:0',
            'platform_sample_qty' => 'required|integer|min:0',
            'min_stock_for_campaign' => 'required|integer|min:0',
        ]);

        Category::where('id', $validated['category_id'])->update([
            'influencer_sample_qty' => $validated['influencer_sample_qty'],
            'affiliate_sample_qty' => $validated['affiliate_sample_qty'],
            'platform_sample_qty' => $validated['platform_sample_qty'],
            'min_stock_for_campaign' => $validated['min_stock_for_campaign'],
        ]);

        return back()->with('success', 'تم حفظ إعدادات الفئة.');
    }
}
