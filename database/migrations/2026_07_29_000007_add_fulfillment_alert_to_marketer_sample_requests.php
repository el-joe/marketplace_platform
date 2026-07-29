<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marketer_sample_requests', function (Blueprint $table) {
            $table->boolean('requires_warehouse_receipt')->default(false)->after('vendor_id');

            $table->char('target_warehouse_id', 36)->nullable()->after('requires_warehouse_receipt')->index();
            $table->foreign('target_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();

            $table->boolean('fulfillment_alert_sent')->default(false)->after('target_warehouse_id');
            $table->timestamp('fulfillment_alert_sent_at')->nullable()->after('fulfillment_alert_sent');
        });
    }

    public function down(): void
    {
        Schema::table('marketer_sample_requests', function (Blueprint $table) {
            $table->dropForeign(['target_warehouse_id']);
            $table->dropColumn([
                'requires_warehouse_receipt',
                'target_warehouse_id',
                'fulfillment_alert_sent',
                'fulfillment_alert_sent_at',
            ]);
        });
    }
};
