<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The 2026_07_30_900001 migration renamed admin_commission_cents /
 * fee_per_celebrity_cents to admin_commission / fee_per_celebrity but
 * never converted the stored values, leaving them at cent magnitude
 * (e.g. 190, 900) while the app now reads them as whole base-currency
 * units. This backfills existing rows to the intended base-currency
 * amounts (e.g. 190 -> 2, 900 -> 9).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('promotion_country_settings')->update([
            'admin_commission' => DB::raw('ROUND(admin_commission / 100)'),
            'fee_per_celebrity' => DB::raw('ROUND(fee_per_celebrity / 100)'),
        ]);
    }

    public function down(): void
    {
        DB::table('promotion_country_settings')->update([
            'admin_commission' => DB::raw('admin_commission * 100'),
            'fee_per_celebrity' => DB::raw('fee_per_celebrity * 100'),
        ]);
    }
};
