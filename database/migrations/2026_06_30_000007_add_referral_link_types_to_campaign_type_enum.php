<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE marketer_campaigns
            MODIFY COLUMN campaign_type ENUM(
                'product_promotion',
                'store_promotion',
                'category_promotion',
                'flash_sale',
                'general',
                'classified_promotion',
                'travel_promotion',
                'product_specific',
                'referral_link',
                'discount_code',
                'brand_deal'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE marketer_campaigns
            SET campaign_type = 'general'
            WHERE campaign_type IN ('referral_link', 'discount_code', 'brand_deal')
        ");

        DB::statement("
            ALTER TABLE marketer_campaigns
            MODIFY COLUMN campaign_type ENUM(
                'product_promotion',
                'store_promotion',
                'category_promotion',
                'flash_sale',
                'general',
                'classified_promotion',
                'travel_promotion',
                'product_specific'
            ) NOT NULL
        ");
    }
};
