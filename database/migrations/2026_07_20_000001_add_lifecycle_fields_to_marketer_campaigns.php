<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 'draft' now means "not yet submitted" (reserved for future use); campaigns
        // submitted via the marketer portal move straight to 'pending_review'.
        DB::statement("
            ALTER TABLE marketer_campaigns
            MODIFY COLUMN status ENUM('draft', 'pending_review', 'active', 'paused', 'rejected', 'ended', 'cancelled')
            NOT NULL DEFAULT 'draft'
        ");

        Schema::table('marketer_campaigns', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('approved_at');
            $table->timestamp('pause_requested_at')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('marketer_campaigns', function (Blueprint $table) {
            $table->dropColumn(['rejection_reason', 'pause_requested_at']);
        });

        DB::statement("UPDATE marketer_campaigns SET status = 'draft' WHERE status IN ('pending_review', 'rejected')");

        DB::statement("
            ALTER TABLE marketer_campaigns
            MODIFY COLUMN status ENUM('draft', 'active', 'paused', 'ended', 'cancelled')
            NOT NULL DEFAULT 'draft'
        ");
    }
};
