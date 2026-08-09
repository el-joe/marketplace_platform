<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customer_dismissed_announcement_bars', function (Blueprint $table) {
            $table->unique(['customer_id', 'announcement_bar_id'], 'cdab_customer_bar_unique');
            $table->index('customer_id', 'cdab_customer_id_index');
            $table->index('announcement_bar_id', 'cdab_announcement_bar_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('customer_dismissed_announcement_bars', function (Blueprint $table) {
            $table->dropUnique('cdab_customer_bar_unique');
            $table->dropIndex('cdab_customer_id_index');
            $table->dropIndex('cdab_announcement_bar_id_index');
        });
    }
};
