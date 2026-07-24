<?php

namespace App\Services;

use App\Models\CartCardOffer;
use App\Models\CountryPaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SavingsBenefitsService
{
    public const CACHE_VERSION_KEY = 'savings_benefits_cache_version';

    /**
     * @param  int    $orderTotal BIGINT base currency (no /100, no *100 — ever)
     * @param  string $countryId  countries.id UUID
     * @param  string $currency   3-char currency code matching the order
     */
    public function get(int $orderTotal, string $countryId, string $currency): array
    {
        $installments = $this->bnplInstallments($orderTotal, $countryId, $currency)
            ->merge($this->bankInstallments($countryId))
            ->sortBy('sort_order')
            ->values();

        return [
            'installments' => $installments->toArray(),
            'card_offers'  => $this->cardOffers($orderTotal, $countryId)->toArray(),
        ];
    }

    private function bnplInstallments(int $orderTotal, string $countryId, string $currency): Collection
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, 1);
        $methods = Cache::remember("benefits:bnpl_v{$version}:{$countryId}", 300, fn () =>
            CountryPaymentMethod::where('country_id', $countryId)
                ->where('method_type', 'bnpl')
                ->active()
                ->orderBy('sort_order')
                ->get([
                    'id', 'provider', 'display_name_en', 'display_name_ar',
                    'installments_count', 'installment_label_en', 'installment_label_ar',
                    'provider_logo_path', 'learn_more_url', 'sort_order',
                    'min_order', 'max_order',
                ])
                ->toArray()
        );

        return collect($methods)
            ->filter(fn (array $m) =>
                (!$m['min_order'] || $m['min_order'] <= $orderTotal)
                && (!$m['max_order'] || $m['max_order'] >= $orderTotal))
            ->map(function (array $m) use ($orderTotal, $currency) {
                $n              = max(1, $m['installments_count']);
                $installmentAmt = intdiv($orderTotal, $n);
                $remainder      = $orderTotal - ($installmentAmt * $n);

                $labelEn = $this->resolveInstallmentLabel(
                    $m['installment_label_en'] ?? 'Pay in {n} interest-free installments of {amount}',
                    $n,
                    $installmentAmt,
                );
                $labelAr = $m['installment_label_ar']
                    ? $this->resolveInstallmentLabel($m['installment_label_ar'], $n, $installmentAmt)
                    : null;

                return [
                    'type'               => 'bnpl',
                    'provider'           => $m['provider'],
                    'display_name_en'    => $m['display_name_en'],
                    'display_name_ar'    => $m['display_name_ar'],
                    'logo_url'           => $this->resolveLogoUrl($m['provider_logo_path']),
                    'installments_count' => $n,
                    'installment_amount' => $installmentAmt,
                    'remainder'          => $remainder,
                    'currency'           => $currency,
                    'label_en'           => $labelEn,
                    'label_ar'           => $labelAr,
                    'learn_more_url'     => $m['learn_more_url'],
                    'sort_order'         => $m['sort_order'],
                ];
            })
            ->values();
    }

    private function bankInstallments(string $countryId): Collection
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, 1);
        $methods = Cache::remember("benefits:bank_v{$version}:{$countryId}", 300, fn () =>
            CountryPaymentMethod::where('country_id', $countryId)
                ->where('method_type', 'bank_transfer')
                ->active()
                ->where('installments_count', '>', 1)
                ->orderBy('sort_order')
                ->get([
                    'id', 'provider', 'display_name_en', 'display_name_ar',
                    'installments_count', 'installment_label_en', 'installment_label_ar',
                    'provider_logo_path', 'learn_more_url', 'sort_order',
                ])
                ->toArray()
        );

        return collect($methods)->map(fn (array $m) => [
            'type'               => 'bank_installment',
            'provider'           => $m['provider'] ?? 'bank',
            'display_name_en'    => $m['display_name_en'],
            'display_name_ar'    => $m['display_name_ar'],
            'logo_url'           => $this->resolveLogoUrl($m['provider_logo_path']),
            'installments_count' => $m['installments_count'],
            'installment_amount' => null,
            'currency'           => null,
            'label_en'           => $m['installment_label_en']
                ?? 'Pay in equal monthly installments with your bank',
            'label_ar'           => $m['installment_label_ar']
                ?? 'ادفع على أقساط شهرية متساوية مع بنكك',
            'learn_more_url'     => $m['learn_more_url'],
            'sort_order'         => $m['sort_order'],
        ])->values();
    }

    private function cardOffers(int $orderTotal, string $countryId): Collection
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, 1);
        $offers = Cache::remember("benefits:card_offers_v{$version}:{$countryId}", 300, fn () =>
            CartCardOffer::where('country_id', $countryId)
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->get()
                ->toArray()
        );

        $now = now();

        return collect($offers)
            ->filter(fn (array $offer) =>
                (!$offer['valid_from'] || \Carbon\Carbon::parse($offer['valid_from'])->lte($now))
                && (!$offer['valid_until'] || \Carbon\Carbon::parse($offer['valid_until'])->gte($now))
                && (!$offer['min_order_amount'] || $offer['min_order_amount'] <= $orderTotal))
            ->map(function (array $offer) use ($orderTotal) {
                $cashback = $offer['cashback_type'] === 'percentage'
                    ? (int) floor($orderTotal * (float) $offer['cashback_pct'] / 100)
                    : $offer['cashback_fixed_amount'];

                if ($offer['max_cashback_amount'] && $cashback > $offer['max_cashback_amount']) {
                    $cashback = $offer['max_cashback_amount'];
                }

                $labelEn = str_replace(
                    ['{amount}', '{card_name}'],
                    [$cashback, $offer['card_name_en']],
                    $offer['label_template_en']
                );
                $labelAr = $offer['label_template_ar']
                    ? str_replace(
                        ['{amount}', '{card_name}'],
                        [$cashback, $offer['card_name_ar']],
                        $offer['label_template_ar']
                    )
                    : null;

                return [
                    'type'            => 'card_cashback',
                    'card_name_en'    => $offer['card_name_en'],
                    'card_name_ar'    => $offer['card_name_ar'],
                    'bank_name_en'    => $offer['bank_name_en'],
                    'bank_name_ar'    => $offer['bank_name_ar'],
                    'card_image_url'  => $this->resolveLogoUrl($offer['card_image_path']),
                    'cashback_type'   => $offer['cashback_type'],
                    'cashback_pct'    => (string) $offer['cashback_pct'],
                    'cashback_amount' => $cashback,
                    'label_en'        => $labelEn,
                    'label_ar'        => $labelAr,
                    'apply_url'       => $offer['apply_url'],
                    'apply_label_en'  => $offer['apply_label_en'],
                    'apply_label_ar'  => $offer['apply_label_ar'],
                    'sort_order'      => $offer['sort_order'],
                ];
            })
            ->values();
    }

    public static function flushCache(): void
    {
        $version = Cache::get(self::CACHE_VERSION_KEY, 1);
        Cache::put(self::CACHE_VERSION_KEY, $version + 1);
    }

    private function resolveInstallmentLabel(string $template, int $n, int $amount): string
    {
        return str_replace(['{n}', '{amount}'], [$n, $amount], $template);
    }

    private function resolveLogoUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, '/')) {
            return config('app.url') . $path;
        }

        return Storage::disk('public')->url($path);
    }
}
