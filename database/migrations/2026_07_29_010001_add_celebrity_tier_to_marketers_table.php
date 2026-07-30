<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            // Which promotion tier(s) this celebrity participates in.
            // JSON array — a celebrity can be in multiple tiers simultaneously.
            // e.g. [1, 2] = participates in Tier 1 (vendor requests) AND Tier 2 (open market)
            // NULL = not a celebrity / no tier assigned yet
            $table->json('celebrity_tiers')
                  ->nullable()
                  ->after('type')
                  ->comment('JSON array of tier numbers [1,2,3,4]. NULL for non-celebrity marketers.');

            // Monthly minimum per tier tracked separately in influencer_monthly_minimums table.
            // This column is just the acceptance window override for this celebrity.
            $table->unsignedSmallInteger('acceptance_window_hours')
                  ->default(24)
                  ->after('celebrity_tiers')
                  ->comment('Hours the celebrity has to accept/reject a promotion request before auto-reassignment.');

            // Total monthly promotion requests received (for priority weighting in auto-assign)
            $table->unsignedInteger('total_promotion_requests_received')
                  ->default(0)
                  ->after('acceptance_window_hours')
                  ->comment('Lifetime total. Used to prioritize celebrities with fewer requests in auto-assign.');
        });
    }

    public function down(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            $table->dropColumn([
                'celebrity_tiers',
                'acceptance_window_hours',
                'total_promotion_requests_received',
            ]);
        });
    }
};
