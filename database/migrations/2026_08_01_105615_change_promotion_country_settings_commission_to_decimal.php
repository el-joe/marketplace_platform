<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promotion_country_settings', function (Blueprint $table) {
            $table->decimal('admin_commission', 10, 2)->change();
            $table->decimal('fee_per_celebrity', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_country_settings', function (Blueprint $table) {
            $table->integer('admin_commission')->change();
            $table->integer('fee_per_celebrity')->change();
        });
    }
};
