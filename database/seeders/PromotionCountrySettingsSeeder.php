<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PromotionCountrySettingsSeeder extends Seeder
{
    /**
     * Owner-specified figures (190 cents = 1.90 SAR admin commission,
     * 900 cents = 9.00 SAR per-celebrity fee) apply as-is only to SA.
     * Other active countries seed with the same minor-unit values as a
     * starting default; admins can override per country later.
     */
    public function run(): void
    {
        $adminCommissionCents  = 190;
        $feePerCelebrityCents  = 900;

        $countryIds = DB::table('countries')->where('is_active', true)->pluck('id');

        $rows = $countryIds->map(fn ($countryId) => [
            'id'                       => (string) Str::uuid(),
            'country_id'               => $countryId,
            'admin_commission_cents'   => $adminCommissionCents,
            'fee_per_celebrity_cents'  => $feePerCelebrityCents,
            'created_at'               => now(),
            'updated_at'               => now(),
        ])->all();

        DB::table('promotion_country_settings')->insertOrIgnore($rows);
    }
}
