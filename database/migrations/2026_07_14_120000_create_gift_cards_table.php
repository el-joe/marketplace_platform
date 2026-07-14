<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->bigInteger('denomination_cents');
            $table->char('currency', 3);
            $table->bigInteger('balance_cents');
            $table->enum('status', ['active', 'redeemed', 'expired', 'cancelled', 'pending_activation'])->default('active');
            $table->foreignUuid('purchased_by_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('recipient_email', 255)->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->string('recipient_name', 100)->nullable();
            $table->text('personal_message')->nullable();
            $table->foreignUuid('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
