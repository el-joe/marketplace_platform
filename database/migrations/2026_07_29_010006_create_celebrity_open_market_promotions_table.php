<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('celebrity_open_market_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The celebrity doing the promoting
            $table->char('promoter_marketer_id', 36)->index('idx_comp_promoter_marketer_id');
            // The celebrity store product being promoted
            $table->char('celebrity_store_product_id', 36)->index('idx_comp_celebrity_store_product_id');
            // The celebrity who owns the product (denormalized for query efficiency)
            $table->char('owner_marketer_id', 36)->index('idx_comp_owner_marketer_id');

            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');

            // Commission earned by promoter — BIGINT base-currency — NO /100
            $table->unsignedBigInteger('commission_earned')->default(0)
                  ->comment('BIGINT base-currency. Populated when a sale occurs via this promotion.');
            $table->char('currency_code', 3)->default('SAR');

            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();

            // CRITICAL BUSINESS RULE: promoter cannot choose >1 product from same owner per month
            $table->unique(['promoter_marketer_id', 'owner_marketer_id', 'month', 'year'],
                           'uq_one_product_per_owner_per_month');

            $table->foreign('promoter_marketer_id', 'fk_comp_promoter_marketer_id')
                  ->references('id')->on('marketers')->onDelete('restrict');
            $table->foreign('celebrity_store_product_id', 'fk_comp_celebrity_store_product_id')
                  ->references('id')->on('celebrity_store_products')->onDelete('restrict');
            $table->foreign('owner_marketer_id', 'fk_comp_owner_marketer_id')
                  ->references('id')->on('marketers')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('celebrity_open_market_promotions');
    }
};
