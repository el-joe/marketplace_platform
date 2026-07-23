<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketer_products', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignUuid('marketer_id')->constrained('marketers')->cascadeOnDelete();
            $table->string('name_en', 255);
            $table->string('name_ar', 255);
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->unsignedBigInteger('price')->comment('base currency unit, no /100');
            $table->char('currency', 3);
            $table->unsignedSmallInteger('platform_commission_rate')->comment('basis points, e.g. 500 = 5%, must be >= admin minimum');
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('sku', 100)->nullable();
            $table->enum('status', ['draft', 'pending_review', 'active', 'paused', 'rejected'])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->string('image_path', 500)->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->boolean('is_digital')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['marketer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketer_products');
    }
};
