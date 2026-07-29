<?php

namespace App\Http\Controllers\MarketerPortal;

use App\Enums\PromotionCategory;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Country;
use App\Models\MarketerCampaign;
use App\Models\MarketerProduct;
use App\Services\MarketerMonthlyQuotaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OpenMarketController extends Controller
{
    public function __construct(private readonly MarketerMonthlyQuotaService $quotaService)
    {
    }

    public function index(Request $request): View
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        $categoryId = $request->input('category_id');
        $minPrice = $request->input('min_price');
        $maxPrice = $request->input('max_price');
        $countryId = $request->input('country_id');

        $promotedSourceMarketerIds = $this->promotedSourceMarketerIdsThisMonth($marketer);

        $products = MarketerProduct::query()
            ->where('status', 'active')
            ->where('marketer_id', '!=', $marketer->id)
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($request->filled('min_price'), fn($q) => $q->where('price', '>=', (int) $minPrice))
            ->when($request->filled('max_price'), fn($q) => $q->where('price', '<=', (int) $maxPrice))
            ->when($countryId, fn($q) => $q->whereHas('marketer', fn($mq) => $mq->where('country_id', $countryId)))
            ->with(['marketer.country', 'category'])
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $products->getCollection()->each(function (MarketerProduct $product) use ($promotedSourceMarketerIds) {
            $product->already_promoted_source = $promotedSourceMarketerIds->contains($product->marketer_id);
        });

        return view('marketer.open-market.index', [
            'marketer' => $marketer,
            'products' => $products,
            'categories' => Category::where('is_active', true)->orderBy('name_en')->get(['id', 'name_en', 'name_ar']),
            'countries' => Country::orderBy('name_en')->get(['id', 'name_en']),
            'filters' => [
                'category_id' => $categoryId,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'country_id' => $countryId,
            ],
        ]);
    }

    public function promote(MarketerProduct $product): RedirectResponse
    {
        /** @var \App\Models\Marketer $marketer */
        $marketer = Auth::guard('marketer')->user();

        abort_if($product->marketer_id === $marketer->id, 403, 'You cannot promote your own product.');
        abort_unless($product->status === 'active', 422, 'This product is not available for promotion.');

        $promotedSourceMarketerIds = $this->promotedSourceMarketerIdsThisMonth($marketer);
        abort_if(
            $promotedSourceMarketerIds->contains($product->marketer_id),
            422,
            'You have already promoted a product from this marketer this month.',
        );

        $campaign = MarketerCampaign::create([
            'marketer_id' => $marketer->id,
            'vendor_id' => null,
            'campaignable_type' => MarketerProduct::class,
            'campaignable_id' => $product->id,
            'name' => $product->name,
            'campaign_type' => 'product_promotion',
            'status' => 'pending_review',
            'commission_type' => 'percentage',
            'commission_rate' => round($product->platform_commission_rate / 100, 2),
            'starts_at' => now(),
            'ends_at' => now()->addYear(),
            'auto_approve_at' => now()->addHours(36),
        ]);

        \App\Jobs\MarketerAutoApproveJob::dispatch($campaign->id, null)
            ->delay(now()->addHours(36));

        $this->quotaService->incrementProgress(
            $marketer,
            PromotionCategory::PeerInfluencerProducts->value,
            now()->year,
            now()->month,
        );

        return redirect()->route('marketer.open-market.index')
            ->with('success', 'Promotion campaign created for "' . $product->name . '".');
    }

    private function promotedSourceMarketerIdsThisMonth($marketer): \Illuminate\Support\Collection
    {
        return MarketerCampaign::query()
            ->where('marketer_id', $marketer->id)
            ->where('campaignable_type', MarketerProduct::class)
            ->where('campaign_type', 'product_promotion')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->with('campaignable:id,marketer_id')
            ->get()
            ->pluck('campaignable.marketer_id')
            ->filter()
            ->unique()
            ->values();
    }
}
