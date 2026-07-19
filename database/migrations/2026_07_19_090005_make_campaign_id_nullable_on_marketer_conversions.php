<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
        });

        DB::statement('ALTER TABLE marketer_conversions MODIFY campaign_id CHAR(36) NULL');

        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
        });

        DB::statement('ALTER TABLE marketer_conversions MODIFY campaign_id CHAR(36) NOT NULL');

        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->cascadeOnDelete();
        });
    }
};
