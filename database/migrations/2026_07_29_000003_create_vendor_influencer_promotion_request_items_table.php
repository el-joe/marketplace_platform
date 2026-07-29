<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendor_influencer_promotion_request_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('promotion_request_id');
            $table->uuid('marketer_id');
            $table->enum('status', ['pending', 'accepted', 'declined', 'timed_out', 'reassigned', 'cancelled'])
                ->default('pending');
            $table->unsignedSmallInteger('acceptance_window_hours')->default(12);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('marketer_note')->nullable();
            $table->text('decline_reason')->nullable();
            $table->unsignedBigInteger('slot_promotion_fee')->default(0);
            $table->uuid('resulting_campaign_id')->nullable();
            $table->uuid('qr_code_id')->nullable();
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('promotion_request_id')
                ->references('id')->on('vendor_influencer_promotion_requests')->cascadeOnDelete();
            $table->foreign('marketer_id')->references('id')->on('marketers');
            $table->foreign('resulting_campaign_id')->references('id')->on('marketer_campaigns');

            $table->index(['marketer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_influencer_promotion_request_items');
    }
};
