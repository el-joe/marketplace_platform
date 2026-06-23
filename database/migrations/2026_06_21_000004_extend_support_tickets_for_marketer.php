<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL requires re-specifying the full ENUM to add a value
        DB::statement("ALTER TABLE support_tickets MODIFY COLUMN requester_role ENUM('customer','seller','marketer') NOT NULL");

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->uuid('related_campaign_id')->nullable()->index()->after('related_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('related_campaign_id');
        });

        DB::statement("ALTER TABLE support_tickets MODIFY COLUMN requester_role ENUM('customer','seller') NOT NULL");
    }
};
