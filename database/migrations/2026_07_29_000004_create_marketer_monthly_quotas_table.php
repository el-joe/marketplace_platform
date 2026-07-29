<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketer_monthly_quotas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // NULL marketer_id = applies to all influencers globally (default quota)
            $table->uuid('marketer_id')->nullable();
            // promotion_category: 1=vendor_requests, 2=peer_influencer_products, 3=admin_intermediary, 4=nawy_now
            $table->unsignedTinyInteger('promotion_category');
            $table->unsignedSmallInteger('min_promotions_per_month');
            // Penalty amount charged if quota not met (in base currency units, per missing promotion)
            $table->unsignedBigInteger('penalty_per_missing')->default(0);
            $table->char('penalty_currency', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('set_by_admin_id');
            $table->timestamps();

            $table->foreign('marketer_id')->references('id')->on('marketers');
            $table->unique(['marketer_id', 'promotion_category'], 'uq_marketer_quota_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketer_monthly_quotas');
    }
};
