<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE marketer_campaigns ADD CONSTRAINT chk_campaign_listing_xor
            CHECK (
                (vendor_listing_id IS NOT NULL AND admin_product_listing_id IS NULL) OR
                (vendor_listing_id IS NULL AND admin_product_listing_id IS NOT NULL)
            )");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE marketer_campaigns DROP CHECK chk_campaign_listing_xor");
    }
};
