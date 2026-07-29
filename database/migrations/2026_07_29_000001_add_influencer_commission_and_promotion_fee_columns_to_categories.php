<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // % of sale price that goes to the influencer (celebrity/influencer type marketers)
            $table->decimal('influencer_commission_pct', 5, 2)->default(0.00)->after('admin_sample_quota');
            // % that the platform takes from the influencer's earned commission
            $table->decimal('admin_cut_from_influencer_pct', 5, 2)->default(0.00)->after('influencer_commission_pct');
            // % of sale price that goes to affiliate/commission marketers
            $table->decimal('affiliate_commission_pct', 5, 2)->default(0.00)->after('admin_cut_from_influencer_pct');
            // % that the platform takes from affiliate marketer earnings
            $table->decimal('admin_cut_from_affiliate_pct', 5, 2)->default(0.00)->after('affiliate_commission_pct');
            // Fixed fee per influencer slot charged to vendor when requesting influencer promotion for a listing in this category
            $table->unsignedBigInteger('promotion_fee_per_influencer')->default(0)->after('admin_cut_from_affiliate_pct');
            // Minimum inventory vendor must have before influencer promotion is allowed
            $table->unsignedSmallInteger('min_stock_for_promotion')->default(0)->after('promotion_fee_per_influencer');
            // Admin-set minimum monthly promotions per category for influencers (Category 1 quota)
            $table->unsignedSmallInteger('influencer_monthly_quota')->default(0)->after('min_stock_for_promotion');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn([
                'influencer_commission_pct',
                'admin_cut_from_influencer_pct',
                'affiliate_commission_pct',
                'admin_cut_from_affiliate_pct',
                'promotion_fee_per_influencer',
                'min_stock_for_promotion',
                'influencer_monthly_quota',
            ]);
        });
    }
};
