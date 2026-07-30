<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_influencer_open_market_products', function (Blueprint $table) {
            // Which tier this admin-curated product belongs to: 3 or 4
            // Tier 3 = any platform product
            // Tier 4 = Nawy Now (admin_product_listings) exclusively
            $table->unsignedTinyInteger('promotion_tier')
                  ->default(3)
                  ->after('open_market_category')
                  ->comment('3 = Admin Curated pool, 4 = Nawy Now products only.');

            // Admin-set commission for celebrities who promote this
            // BIGINT base-currency — NO /100
            $table->unsignedBigInteger('celebrity_commission_amount')
                  ->default(0)
                  ->after('promotion_tier')
                  ->comment('Commission celebrity earns per sale. BIGINT base-currency. No /100.');
            $table->char('commission_currency', 3)->default('SAR')->after('celebrity_commission_amount');
        });

        // CONSTRAINT: Tier 4 products must use admin_product_listing_id (not vendor_listing_id)
        DB::statement('ALTER TABLE admin_influencer_open_market_products
          ADD CONSTRAINT chk_tier4_admin_only
          CHECK (promotion_tier != 4 OR admin_product_listing_id IS NOT NULL)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE admin_influencer_open_market_products DROP CONSTRAINT chk_tier4_admin_only');

        Schema::table('admin_influencer_open_market_products', function (Blueprint $table) {
            $table->dropColumn(['promotion_tier', 'celebrity_commission_amount', 'commission_currency']);
        });
    }
};
