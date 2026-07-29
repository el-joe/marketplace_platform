<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_influencer_promotion_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_id');
            $table->uuid('vendor_listing_id')->nullable();
            $table->uuid('admin_product_listing_id')->nullable();
            $table->enum('status', ['pending', 'partially_accepted', 'fully_accepted', 'completed', 'cancelled'])
                ->default('pending');
            $table->enum('listing_fulfillment_model', ['fbm', 'fbn', 'cross_dock', 'admin_express', 'admin_global'])
                ->nullable();
            $table->boolean('requires_warehouse_receipt')->default(false);
            $table->boolean('warehouse_receipt_confirmed')->default(false);
            $table->uuid('confirmed_by_admin_id')->nullable();
            $table->unsignedBigInteger('total_promotion_fee')->default(0);
            $table->char('currency', 3);
            $table->enum('fee_deduction_timing', ['weekly_settlement', 'immediate'])->default('weekly_settlement');
            $table->boolean('fee_deducted')->default(false);
            $table->timestamp('fee_deducted_at')->nullable();
            $table->text('vendor_note')->nullable();
            $table->uuid('created_by_admin_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('vendor_id', 'vipr_vendor_id_foreign')->references('id')->on('vendors');
            $table->foreign('vendor_listing_id', 'vipr_vendor_listing_id_foreign')->references('id')->on('vendor_listings');
            $table->foreign('admin_product_listing_id', 'vipr_admin_product_listing_id_foreign')->references('id')->on('admin_product_listings');

            $table->index(['vendor_id', 'status']);
        });

        DB::statement('ALTER TABLE vendor_influencer_promotion_requests ADD CONSTRAINT chk_vipr_listing_xor
            CHECK ((vendor_listing_id IS NOT NULL AND admin_product_listing_id IS NULL)
                OR (vendor_listing_id IS NULL AND admin_product_listing_id IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_influencer_promotion_requests');
    }
};
