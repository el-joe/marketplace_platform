<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_influencer_promotion_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('num_celebrities_requested')
                  ->default(1)
                  ->after('vendor_id')
                  ->comment('How many celebrity slots the vendor selected at request time.');

            $table->unsignedBigInteger('fixed_admin_commission_snapshot')
                  ->default(0)
                  ->after('total_promotion_fee')
                  ->comment('Snapshot of settings.promotion_fixed_admin_commission at request time. BIGINT, no /100.');

            $table->unsignedBigInteger('admin_sample_debt')
                  ->default(0)
                  ->after('fixed_admin_commission_snapshot')
                  ->comment('Value admin owes vendor for the admin sample. BIGINT base-currency. No /100.');

            $table->boolean('admin_sample_debt_settled')->default(false)->after('admin_sample_debt');

            $table->unsignedBigInteger('promotion_fee_per_celebrity_snapshot')
                  ->default(0)
                  ->after('admin_sample_debt_settled')
                  ->comment('Snapshot of settings.promotion_fee_per_celebrity at request time. BIGINT, no /100.');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_influencer_promotion_requests', function (Blueprint $table) {
            $table->dropColumn([
                'num_celebrities_requested',
                'fixed_admin_commission_snapshot',
                'admin_sample_debt',
                'admin_sample_debt_settled',
                'promotion_fee_per_celebrity_snapshot',
            ]);
        });
    }
};
