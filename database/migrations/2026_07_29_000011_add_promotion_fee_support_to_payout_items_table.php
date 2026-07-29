<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE payout_items MODIFY sub_order_id CHAR(36) NULL');

        Schema::table('payout_items', function (Blueprint $table) {
            $table->enum('item_type', ['sub_order', 'promotion_fee'])->default('sub_order')->after('payout_id');
            $table->uuid('promotion_request_id')->nullable()->after('sub_order_id')->index();
            $table->string('description', 500)->nullable()->after('net');
        });
    }

    public function down(): void
    {
        Schema::table('payout_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'promotion_request_id', 'description']);
        });

        DB::statement('ALTER TABLE payout_items MODIFY sub_order_id CHAR(36) NOT NULL');
    }
};
