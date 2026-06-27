<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marketer_campaigns', function (Blueprint $table) {
            $table->string('campaignable_type', 200)->nullable()->after('vendor_id');
            $table->char('campaignable_id', 36)->nullable()->after('campaignable_type');
        });

        // Backfill: every existing campaign targets a vendor's store as a whole
        DB::statement("
            UPDATE marketer_campaigns
            SET campaignable_type = 'App\\\\Models\\\\Vendor',
                campaignable_id   = vendor_id
            WHERE vendor_id IS NOT NULL
              AND campaignable_id IS NULL
        ");

        Schema::table('marketer_campaigns', function (Blueprint $table) {
            $table->index(['campaignable_type', 'campaignable_id'], 'campaigns_campaignable_index');
        });
    }

    public function down(): void
    {
        Schema::table('marketer_campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_campaignable_index');
            $table->dropColumn(['campaignable_type', 'campaignable_id']);
        });
    }
};
