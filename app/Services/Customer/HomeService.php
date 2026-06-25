<?php

namespace App\Services\Customer;

use App\Models\Country;
use App\Models\Customer;
use App\Models\FlashSale;
use Illuminate\Support\Carbon;

class HomeService
{
    public function __construct(
        private readonly PageRendererService $pageRenderer,
        private readonly NavigationService $navigation,
    ) {}

    public function getHomeData(Country $country, ?Customer $customer, string $sessionId): array
    {
        $page = $this->pageRenderer->render('home', null, $country, $customer, $sessionId);
        $nav  = $this->navigation->getTree($country);

        return [
            'page'             => $page,
            'nav'              => $nav,
            'flash_sale_banner' => $this->activeFlashSaleBanner($country),
        ];
    }

    private function activeFlashSaleBanner(Country $country): ?array
    {
        $now  = Carbon::now();
        $sale = FlashSale::where('country_id', $country->id)
            ->where('status', 'live')
            ->where('sale_starts_at', '<=', $now)
            ->where('sale_ends_at', '>', $now)
            ->orderBy('sale_ends_at')
            ->first();

        if (! $sale) {
            return null;
        }

        return [
            'id'       => $sale->id,
            'name'     => $sale->name_en,
            'name_ar'  => $sale->name_ar,
            'ends_at'  => $sale->sale_ends_at,
        ];
    }
}
