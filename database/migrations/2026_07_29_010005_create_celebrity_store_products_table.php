<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('celebrity_store_products', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // The celebrity who owns this product listing in their store
            $table->char('celebrity_marketer_id', 36)->index()
                  ->comment('The celebrity who added this product to their store.');

            // Links to either a vendor listing or admin listing
            $table->char('vendor_listing_id', 36)->nullable();
            $table->char('admin_product_listing_id', 36)->nullable();

            // Admin-set commission for anyone who promotes this product
            // BIGINT base-currency — NO /100
            $table->unsignedBigInteger('promoter_commission_amount')
                  ->comment('Commission earned by the celebrity who promotes this. Set by admin. BIGINT, no /100.');
            $table->char('currency_code', 3)->default('SAR');

            // Admin's cut (shown to promoter so they know how much admin takes)
            // Stored as basis points (0–10000) — this is commission rate not a monetary amount
            $table->unsignedSmallInteger('admin_commission_pct_bps')
                  ->default(0)
                  ->comment('Admin commission in basis points (e.g. 500 = 5%). Display-only to promoter.');

            $table->boolean('is_active')->default(true);
            $table->char('approved_by_admin_id', 36)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('celebrity_marketer_id')->references('id')->on('marketers')->onDelete('cascade');
            $table->foreign('approved_by_admin_id')->references('id')->on('admins')->onDelete('set null');
        });

        // MySQL doesn't support CHECK constraints via rawIndex; enforce XOR via DB::statement.
        DB::statement('ALTER TABLE celebrity_store_products ADD CONSTRAINT chk_celebrity_store_xor
            CHECK ((vendor_listing_id IS NOT NULL) XOR (admin_product_listing_id IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('celebrity_store_products');
    }
};
