<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketer_campaigns', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignUuid('marketer_id')->constrained('marketers');
            $table->char('vendor_id', 36)->nullable()->index()->comment('NULL = platform-wide campaign');
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->enum('campaign_type', [
                'product_promotion',
                'store_promotion',
                'category_promotion',
                'flash_sale',
                'general',
            ]);
            $table->enum('status', ['draft', 'active', 'paused', 'ended', 'cancelled'])->default('draft');
            $table->decimal('commission_rate', 5, 2)->comment('Overrides marketer.commission_rate if set');
            $table->enum('commission_type', ['percentage', 'flat_per_order', 'flat_per_click'])->default('percentage');
            $table->bigInteger('budget_cents')->nullable()->comment('NULL = unlimited');
            $table->bigInteger('budget_spent_cents')->default(0);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('tracking_url_slug', 100)->unique()->comment('e.g. /r/ahmed-electronics');
            $table->integer('total_clicks')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->bigInteger('total_revenue_cents')->default(0);
            $table->char('created_by_admin_id', 36)->nullable()->index();
            $table->char('approved_by_admin_id', 36)->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['marketer_id', 'status']);
            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('marketer_campaign_products', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('campaign_id', 36)->index();
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->cascadeOnDelete();
            $table->foreignUuid('vendor_listing_id')->constrained('vendor_listings');
            $table->integer('position')->default(0);
            $table->decimal('commission_override', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'vendor_listing_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketer_campaign_products');
        Schema::dropIfExists('marketer_campaigns');
    }
};
