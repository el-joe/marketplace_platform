<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_country_settings', function (Blueprint $table) {
            $table->renameColumn('admin_commission_cents', 'admin_commission');
            $table->renameColumn('fee_per_celebrity_cents', 'fee_per_celebrity');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_country_settings', function (Blueprint $table) {
            $table->renameColumn('admin_commission', 'admin_commission_cents');
            $table->renameColumn('fee_per_celebrity', 'fee_per_celebrity_cents');
        });
    }
};
