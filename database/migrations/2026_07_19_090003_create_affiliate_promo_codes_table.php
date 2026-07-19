<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('affiliate_promo_codes', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignUuid('marketer_id')->constrained('marketers');
            $table->char('campaign_id', 36)->nullable()->index();
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->nullOnDelete();
            $table->string('code', 50)->unique();
            $table->enum('discount_type', ['percentage', 'fixed_amount', 'free_shipping']);
            $table->decimal('discount_value', 10, 2);
            $table->char('currency', 3)->nullable()->comment('required when discount_type = fixed_amount');
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->bigInteger('min_order_amount')->nullable();
            $table->timestamp('valid_from');
            $table->timestamp('valid_until');
            $table->boolean('is_active')->default(true);
            $table->bigInteger('total_revenue_generated')->default(0);
            $table->bigInteger('total_commission_earned')->default(0);
            $table->timestamps();

            $table->index(['marketer_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_promo_codes');
    }
};
