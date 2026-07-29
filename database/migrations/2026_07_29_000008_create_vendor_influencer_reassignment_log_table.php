<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_influencer_reassignment_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('promotion_request_id');
            $table->uuid('promotion_request_item_id');
            $table->uuid('from_marketer_id');
            $table->uuid('to_marketer_id');
            $table->enum('reason', ['declined', 'timed_out'])->default('timed_out');
            $table->unsignedInteger('to_marketer_request_count_30d')->default(0);
            $table->uuid('triggered_by_admin_id')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('promotion_request_id')
                ->references('id')->on('vendor_influencer_promotion_requests');
            $table->foreign('promotion_request_item_id')
                ->references('id')->on('vendor_influencer_promotion_request_items');
            $table->foreign('from_marketer_id')->references('id')->on('marketers');
            $table->foreign('to_marketer_id')->references('id')->on('marketers');

            $table->index(['to_marketer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_influencer_reassignment_log');
    }
};
