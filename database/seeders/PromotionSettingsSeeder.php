<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromotionSettingsSeeder extends Seeder
{
    /**
     * Country-varying monetary amounts (admin commission, per-celebrity fee)
     * live in promotion_country_settings, seeded by PromotionCountrySettingsSeeder.
     * Only global, non-monetary promotion settings go here.
     */
    public function run(): void
    {
        $settings = [
            [
                'key'         => 'promotion_fixed_admin_commission',
                'value'       => 2,
                'description' => 'Fixed admin commission per promotion request, in base currency units.',
            ],
            [
                'key'         => 'promotion_fee_per_celebrity',
                'value'       => 9,
                'description' => 'Promotion fee charged per celebrity slot, in base currency units.',
            ],
            [
                'key'         => 'promotion_request_acceptance_window_hours',
                'value'       => 24,
                'description' => 'Hours a celebrity has to accept/reject a promotion request before auto-reassignment.',
            ],
            [
                'key'         => 'promotion_min_stock_multiplier',
                'value'       => 3,
                'description' => 'Minimum stock = num_celebrities x this multiplier. Admin can also set per-listing override.',
            ],
            [
                'key'         => 'promotion_tier1_monthly_minimum',
                'value'       => 3,
                'description' => 'Tier 1: minimum vendor promotion requests a celebrity must fulfill per month.',
            ],
            [
                'key'         => 'promotion_tier2_monthly_minimum',
                'value'       => 4,
                'description' => 'Tier 2: minimum open market promotions a celebrity must run per month.',
            ],
            [
                'key'         => 'promotion_tier3_monthly_minimum',
                'value'       => 3,
                'description' => 'Tier 3: minimum admin-curated promotions per month.',
            ],
            [
                'key'         => 'promotion_tier4_monthly_minimum',
                'value'       => 3,
                'description' => 'Tier 4: minimum Nawy Now product promotions per month.',
            ],
        ];

        $rows = array_map(fn (array $s) => [
            'id'            => (string) Str::uuid(),
            'key'           => $s['key'],
            'value'         => json_encode($s['value']),
            'category'      => 'promotion',
            'description'   => $s['description'],
            'is_encrypted'  => false,
            'is_public'     => false,
        ], $settings);

        DB::table('settings')->insertOrIgnore($rows);
    }
}
