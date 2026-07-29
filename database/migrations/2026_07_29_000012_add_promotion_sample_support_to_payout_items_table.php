<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE payout_items MODIFY item_type ENUM('sub_order', 'promotion_fee', 'promotion_sample') DEFAULT 'sub_order'");

        Schema::table('payout_items', function (Blueprint $table) {
            $table->uuid('sample_item_id')->nullable()->after('promotion_request_id')->index();
        });

        Schema::table('marketer_sample_items', function (Blueprint $table) {
            $table->boolean('fee_deducted')->default(false)->after('sample_cost');
            $table->timestamp('fee_deducted_at')->nullable()->after('fee_deducted');
        });
    }

    public function down(): void
    {
        Schema::table('marketer_sample_items', function (Blueprint $table) {
            $table->dropColumn(['fee_deducted', 'fee_deducted_at']);
        });

        Schema::table('payout_items', function (Blueprint $table) {
            $table->dropColumn('sample_item_id');
        });

        DB::statement("ALTER TABLE payout_items MODIFY item_type ENUM('sub_order', 'promotion_fee') DEFAULT 'sub_order'");
    }
};
