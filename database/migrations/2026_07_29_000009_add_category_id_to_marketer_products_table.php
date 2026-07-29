<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marketer_products', function (Blueprint $table) {
            $table->char('category_id', 36)->nullable()->after('marketer_id');
            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->index(['status', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::table('marketer_products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['status', 'category_id']);
            $table->dropColumn('category_id');
        });
    }
};
