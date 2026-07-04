<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketer_sample_items', function (Blueprint $table) {
            $table->integer('marketer_quantity')->default(0)->after('quantity')
                ->comment('Qty visible to marketer only');
            $table->integer('admin_quantity')->default(0)->after('marketer_quantity')
                ->comment('Secret admin qty — hidden from marketer and vendor');
        });
    }

    public function down(): void
    {
        Schema::table('marketer_sample_items', function (Blueprint $table) {
            $table->dropColumn(['marketer_quantity', 'admin_quantity']);
        });
    }
};
