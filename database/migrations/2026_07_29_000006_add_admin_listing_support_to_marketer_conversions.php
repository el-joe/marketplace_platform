<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE marketer_conversions MODIFY vendor_id CHAR(36) NULL');

        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->char('admin_product_listing_id', 36)->nullable()->after('vendor_id')->index();
            $table->foreign('admin_product_listing_id')->references('id')->on('admin_product_listings')->restrictOnDelete();
        });

        DB::statement(<<<'SQL'
            ALTER TABLE marketer_conversions
            ADD CONSTRAINT chk_mc_source_xor CHECK (
                (vendor_id IS NOT NULL AND admin_product_listing_id IS NULL)
                OR
                (vendor_id IS NULL AND admin_product_listing_id IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE marketer_conversions DROP CHECK chk_mc_source_xor');

        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->dropForeign(['admin_product_listing_id']);
            $table->dropColumn('admin_product_listing_id');
        });

        DB::statement('ALTER TABLE marketer_conversions MODIFY vendor_id CHAR(36) NOT NULL');
    }
};
