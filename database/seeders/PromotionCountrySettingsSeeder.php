<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromotionCountrySettingsSeeder extends Seeder
{
    /**
     * Columns are BIGINT base-currency units (no /100). Owner specified
     * 1.9 SAR fixed admin commission; rounded to 2 since these columns
     * store whole base-currency units. VERIFY with owner before going live.
     */
    public function run(): void
    {
        return;
        $adminCommission  = 2;
        $feePerCelebrity  = 9;

        $countryIds = DB::table('countries')->where('is_active', true)->pluck('id');

        $rows = $countryIds->map(fn ($countryId) => [
            'id'                 => (string) Str::uuid(),
            'country_id'         => $countryId,
            'admin_commission'   => $adminCommission,
            'fee_per_celebrity'  => $feePerCelebrity,
            'created_at'         => now(),
            'updated_at'         => now(),
        ])->all();

        DB::table('promotion_country_settings')->insertOrIgnore($rows);
    }
}
