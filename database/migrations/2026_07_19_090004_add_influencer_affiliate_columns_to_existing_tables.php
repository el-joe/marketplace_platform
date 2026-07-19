<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marketer_campaigns', function (Blueprint $table) {
            $table->char('influencer_deal_id', 36)->nullable()->index()
                ->after('secret_promotion_id');
            $table->foreign('influencer_deal_id')
                ->references('id')->on('influencer_deals')
                ->nullOnDelete();
        });

        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->char('affiliate_promo_code_id', 36)->nullable()->index()
                ->after('whatsapp_link_id');
            $table->foreign('affiliate_promo_code_id')
                ->references('id')->on('affiliate_promo_codes')
                ->nullOnDelete();
        });

        Schema::table('marketer_payouts', function (Blueprint $table) {
            $table->char('influencer_deal_id', 36)->nullable()->index()
                ->after('marketer_id');
            $table->foreign('influencer_deal_id')
                ->references('id')->on('influencer_deals')
                ->nullOnDelete();
            $table->enum('payout_type', ['commission', 'flat_fee', 'hybrid'])
                ->default('commission')
                ->after('payout_number');
        });
    }

    public function down(): void
    {
        Schema::table('marketer_payouts', function (Blueprint $table) {
            $table->dropForeign(['influencer_deal_id']);
            $table->dropColumn(['influencer_deal_id', 'payout_type']);
        });

        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->dropForeign(['affiliate_promo_code_id']);
            $table->dropColumn('affiliate_promo_code_id');
        });

        Schema::table('marketer_campaigns', function (Blueprint $table) {
            $table->dropForeign(['influencer_deal_id']);
            $table->dropColumn('influencer_deal_id');
        });
    }
};
