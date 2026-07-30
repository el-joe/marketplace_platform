<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketer_category_commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('marketer_id', 36)->index()
                  ->comment('Must be a celebrity-type marketer.');
            $table->char('category_id', 36)->index();

            // Commission amount — BIGINT base-currency units — NO /100
            $table->unsignedBigInteger('commission_amount')
                  ->comment('BIGINT base-currency. e.g. 18 = 18 SAR per sale. Never /100.');
            $table->char('currency_code', 3)->default('SAR');

            $table->boolean('is_active')->default(true);
            $table->char('set_by_admin_id', 36)->nullable();
            $table->timestamps();

            $table->unique(['marketer_id', 'category_id']);

            $table->foreign('marketer_id')->references('id')->on('marketers')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('set_by_admin_id')->references('id')->on('admins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketer_category_commissions');
    }
};
