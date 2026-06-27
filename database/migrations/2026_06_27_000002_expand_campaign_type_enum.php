<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
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
                'product_specific'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        // Remove rows with new types before shrinking enum to avoid constraint errors
        DB::statement("
            UPDATE marketer_campaigns
            SET campaign_type = 'general'
            WHERE campaign_type IN ('classified_promotion', 'travel_promotion', 'product_specific')
        ");

        DB::statement("
            ALTER TABLE marketer_campaigns
            MODIFY COLUMN campaign_type ENUM(
                'product_promotion',
                'store_promotion',
                'category_promotion',
                'flash_sale',
                'general'
            ) NOT NULL
        ");
    }
};
