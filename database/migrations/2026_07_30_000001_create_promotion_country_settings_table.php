<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotion_country_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('country_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('admin_commission_cents');
            $table->unsignedBigInteger('fee_per_celebrity_cents');
            $table->timestamps();

            $table->unique('country_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_country_settings');
    }
};
