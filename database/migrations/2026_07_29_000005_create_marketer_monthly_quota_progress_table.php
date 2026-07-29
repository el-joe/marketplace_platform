<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketer_monthly_quota_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('marketer_id');
            $table->unsignedTinyInteger('promotion_category');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('quota_target');
            $table->unsignedSmallInteger('completed_count')->default(0);
            $table->enum('status', ['in_progress', 'met', 'warning_sent', 'failed', 'penalised'])
                ->default('in_progress');
            $table->boolean('warning_sent')->default(false);
            $table->timestamp('warning_sent_at')->nullable();
            $table->boolean('penalty_applied')->default(false);
            $table->unsignedBigInteger('penalty_amount')->default(0);
            $table->timestamp('penalty_applied_at')->nullable();
            $table->timestamps();

            $table->foreign('marketer_id')->references('id')->on('marketers');
            $table->unique(['marketer_id', 'promotion_category', 'year', 'month'], 'uq_mq_progress');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketer_monthly_quota_progress');
    }
};
