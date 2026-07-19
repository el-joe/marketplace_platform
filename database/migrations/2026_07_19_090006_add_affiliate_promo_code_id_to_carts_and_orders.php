<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->uuid('affiliate_promo_code_id')->nullable()->index()->after('coupon_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('affiliate_promo_code_id')->nullable()->index()->after('coupon_code_used');
            $table->bigInteger('affiliate_commission_amount')->nullable()->after('affiliate_promo_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('affiliate_promo_code_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['affiliate_promo_code_id', 'affiliate_commission_amount']);
        });
    }
};
