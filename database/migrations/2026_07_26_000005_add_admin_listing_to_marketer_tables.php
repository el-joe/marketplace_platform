<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // marketer_campaign_products
        // ─────────────────────────────────────────────────────────────────────
        Schema::table('marketer_campaign_products', function (Blueprint $table) {
            $table->dropUnique('marketer_campaign_products_campaign_id_vendor_listing_id_unique');
        });

        DB::statement('ALTER TABLE marketer_campaign_products MODIFY vendor_listing_id CHAR(36) NULL');

        Schema::table('marketer_campaign_products', function (Blueprint $table) {
            $table->char('admin_product_listing_id', 36)->nullable()->after('vendor_listing_id')->index();
            $table->foreign('admin_product_listing_id')->references('id')->on('admin_product_listings')->nullOnDelete();
        });

        // Note: uniqueness of (campaign_id, vendor_listing_id) and
        // (campaign_id, admin_product_listing_id) when set is enforced at the
        // application layer, since MySQL does not support partial/filtered
        // unique indexes with NULL exclusion.

        // ─────────────────────────────────────────────────────────────────────
        // marketer_secret_promotions
        // ─────────────────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE marketer_secret_promotions MODIFY vendor_id CHAR(36) NULL');
        DB::statement('ALTER TABLE marketer_secret_promotions MODIFY vendor_listing_id CHAR(36) NULL');

        Schema::table('marketer_secret_promotions', function (Blueprint $table) {
            $table->char('admin_product_listing_id', 36)->nullable()->after('vendor_listing_id')->index();
            $table->foreign('admin_product_listing_id')->references('id')->on('admin_product_listings')->nullOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE marketer_secret_promotions
            ADD CONSTRAINT chk_msp_listing_xor CHECK (
                (vendor_listing_id IS NOT NULL AND admin_product_listing_id IS NULL)
                OR
                (vendor_listing_id IS NULL AND admin_product_listing_id IS NOT NULL)
            )
        SQL);

        // ─────────────────────────────────────────────────────────────────────
        // marketer_qr_codes
        // ─────────────────────────────────────────────────────────────────────
        Schema::table('marketer_qr_codes', function (Blueprint $table) {
            $table->char('admin_product_listing_id', 36)->nullable()->after('vendor_listing_id')->index();
            $table->foreign('admin_product_listing_id')->references('id')->on('admin_product_listings')->nullOnDelete();
        });

        // ─────────────────────────────────────────────────────────────────────
        // marketer_sample_items
        // ─────────────────────────────────────────────────────────────────────
        DB::statement('ALTER TABLE marketer_sample_items MODIFY vendor_listing_id CHAR(36) NULL');

        Schema::table('marketer_sample_items', function (Blueprint $table) {
            $table->char('admin_product_listing_id', 36)->nullable()->after('vendor_listing_id')->index();
            $table->foreign('admin_product_listing_id')->references('id')->on('admin_product_listings')->nullOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE marketer_sample_items
            ADD CONSTRAINT chk_msi_listing_xor CHECK (
                (vendor_listing_id IS NOT NULL AND admin_product_listing_id IS NULL)
                OR
                (vendor_listing_id IS NULL AND admin_product_listing_id IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE marketer_sample_items DROP CHECK chk_msi_listing_xor');

        Schema::table('marketer_sample_items', function (Blueprint $table) {
            $table->dropForeign(['admin_product_listing_id']);
            $table->dropColumn('admin_product_listing_id');
        });

        DB::statement('ALTER TABLE marketer_sample_items MODIFY vendor_listing_id CHAR(36) NOT NULL');

        Schema::table('marketer_qr_codes', function (Blueprint $table) {
            $table->dropForeign(['admin_product_listing_id']);
            $table->dropColumn('admin_product_listing_id');
        });

        DB::statement('ALTER TABLE marketer_secret_promotions DROP CHECK chk_msp_listing_xor');

        Schema::table('marketer_secret_promotions', function (Blueprint $table) {
            $table->dropForeign(['admin_product_listing_id']);
            $table->dropColumn('admin_product_listing_id');
        });

        DB::statement('ALTER TABLE marketer_secret_promotions MODIFY vendor_listing_id CHAR(36) NOT NULL');
        DB::statement('ALTER TABLE marketer_secret_promotions MODIFY vendor_id CHAR(36) NOT NULL');

        Schema::table('marketer_campaign_products', function (Blueprint $table) {
            $table->dropForeign(['admin_product_listing_id']);
            $table->dropColumn('admin_product_listing_id');
        });

        DB::statement('ALTER TABLE marketer_campaign_products MODIFY vendor_listing_id CHAR(36) NOT NULL');

        Schema::table('marketer_campaign_products', function (Blueprint $table) {
            $table->unique(['campaign_id', 'vendor_listing_id'], 'marketer_campaign_products_campaign_id_vendor_listing_id_unique');
        });
    }
};
