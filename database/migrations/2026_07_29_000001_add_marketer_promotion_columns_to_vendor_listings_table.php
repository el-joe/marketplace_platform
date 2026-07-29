<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vendor_listings', function (Blueprint $table) {
            $table->boolean('available_for_marketers')->default(false)->after('low_stock_threshold');
            // % commission offered to influencers for this listing (overrides category default if set)
            $table->decimal('influencer_commission_pct', 5, 2)->nullable()->after('available_for_marketers');
            // % commission offered to affiliate marketers (overrides category default if set)
            $table->decimal('affiliate_commission_pct', 5, 2)->nullable()->after('influencer_commission_pct');
            // Fixed fee per influencer slot charged to vendor (overrides category default if set)
            $table->unsignedBigInteger('promotion_fee_per_influencer_override')->nullable()->after('affiliate_commission_pct');
            // Minimum inventory units required before promotion can be activated (admin can block below threshold)
            $table->unsignedInteger('min_stock_for_promotion')->default(0)->after('promotion_fee_per_influencer_override');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_listings', function (Blueprint $table) {
            $table->dropColumn([
                'available_for_marketers',
                'influencer_commission_pct',
                'affiliate_commission_pct',
                'promotion_fee_per_influencer_override',
                'min_stock_for_promotion',
            ]);
        });
    }
};
