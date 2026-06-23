<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketer_payouts', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('payout_number', 30)->unique();
            $table->foreignUuid('marketer_id')->constrained('marketers');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_conversions')->default(0);
            $table->bigInteger('gross_commission_cents')->default(0);
            $table->bigInteger('tax_deduction_cents')->default(0);
            $table->bigInteger('net_amount_cents')->default(0);
            $table->char('currency', 3);
            $table->enum('status', ['pending', 'approved', 'paid', 'failed'])->default('pending');
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_iban', 50)->nullable();
            $table->char('approved_by_admin_id', 36)->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->timestamps();

            $table->index(['marketer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketer_payouts');
    }
};
