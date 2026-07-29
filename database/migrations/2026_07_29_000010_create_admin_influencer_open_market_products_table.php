<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_influencer_open_market_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('vendor_listing_id')->nullable();
            $table->uuid('admin_product_listing_id')->nullable();
            $table->unsignedTinyInteger('open_market_category'); // 3 = admin_intermediary, 4 = nawy_now
            $table->boolean('is_active')->default(true);
            $table->uuid('added_by_admin_id');
            $table->timestamps();

            $table->foreign('vendor_listing_id', 'aiomp_vendor_listing_id_foreign')
                ->references('id')->on('vendor_listings');
            $table->foreign('admin_product_listing_id', 'aiomp_admin_product_listing_id_foreign')
                ->references('id')->on('admin_product_listings');
            $table->foreign('added_by_admin_id', 'aiomp_added_by_admin_id_foreign')
                ->references('id')->on('admins');

            $table->index(['open_market_category', 'is_active'], 'aiomp_category_active_index');
        });

        DB::statement("ALTER TABLE admin_influencer_open_market_products ADD CONSTRAINT chk_aiomp_xor
            CHECK ((vendor_listing_id IS NOT NULL AND admin_product_listing_id IS NULL)
                OR (vendor_listing_id IS NULL AND admin_product_listing_id IS NOT NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_influencer_open_market_products');
    }
};
