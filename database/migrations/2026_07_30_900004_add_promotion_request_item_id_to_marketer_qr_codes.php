<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketer_qr_codes', function (Blueprint $table) {
            $table->char('promotion_request_item_id', 36)
                ->nullable()
                ->after('campaign_id')
                ->comment('FK to vendor_influencer_promotion_request_items. NULL if QR is not from a promotion request.');

            $table->foreign('promotion_request_item_id')
                ->references('id')
                ->on('vendor_influencer_promotion_request_items')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('marketer_qr_codes', function (Blueprint $table) {
            $table->dropForeign(['promotion_request_item_id']);
            $table->dropColumn('promotion_request_item_id');
        });
    }
};
