<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketer_clicks', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('campaign_id', 36)->index();
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->cascadeOnDelete();
            $table->foreignUuid('marketer_id')->constrained('marketers');
            $table->char('customer_id', 36)->nullable()->index();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->string('session_id', 100);
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('referrer_url', 500)->nullable();
            $table->char('landing_product_id', 36)->nullable()->index();
            $table->foreign('landing_product_id')->references('id')->on('products')->nullOnDelete();
            $table->string('device_type', 20)->nullable();
            $table->char('country_id', 36)->nullable()->index();
            $table->foreign('country_id')->references('id')->on('countries')->nullOnDelete();
            $table->timestamp('clicked_at')->useCurrent();
            $table->tinyInteger('converted')->default(0);
            $table->char('conversion_order_id', 36)->nullable()->index();
            $table->foreign('conversion_order_id')->references('id')->on('orders')->nullOnDelete();

            $table->index(['campaign_id', 'clicked_at'], 'idx_campaign_clicked');
            $table->index(['marketer_id', 'clicked_at'], 'idx_marketer_clicked');
        });

        Schema::create('marketer_conversions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('campaign_id', 36)->index();
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->cascadeOnDelete();
            $table->foreignUuid('marketer_id')->constrained('marketers');
            $table->char('click_id', 36)->nullable()->index();
            $table->foreign('click_id')->references('id')->on('marketer_clicks')->nullOnDelete();
            $table->foreignUuid('order_id')->constrained('orders');
            $table->char('order_item_id', 36)->nullable()->index();
            $table->foreign('order_item_id')->references('id')->on('order_items')->nullOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers');
            $table->foreignUuid('vendor_id')->constrained('vendors');
            $table->bigInteger('order_value_cents');
            $table->decimal('commission_rate', 5, 2);
            $table->bigInteger('commission_amount_cents');
            $table->char('currency', 3);
            $table->enum('status', ['pending', 'approved', 'paid', 'reversed'])->default('pending')
                ->comment('reversed if order refunded');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['marketer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketer_conversions');
        Schema::dropIfExists('marketer_clicks');
    }
};
