<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classified_listing_marketers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('classified_listing_id');
            $table->uuid('marketer_id')->nullable();
            $table->enum('commission_type', ['fixed', 'percentage']);
            $table->decimal('commission_value', 10, 2);
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->timestamps();

            $table->foreign('classified_listing_id')->references('id')->on('classified_listings')->cascadeOnDelete();
            $table->foreign('marketer_id')->references('id')->on('marketers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classified_listing_marketers');
    }
};
