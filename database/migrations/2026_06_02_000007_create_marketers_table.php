<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('marketers', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('phone', 30)->nullable();
            $table->string('password', 255);
            $table->enum('type', ['influencer', 'celebrity', 'affiliate', 'brand_ambassador']);
            $table->text('bio')->nullable();
            $table->string('profile_photo_path', 255)->nullable();
            $table->foreignUuid('country_id')->constrained('countries');
            $table->string('social_instagram', 255)->nullable();
            $table->string('social_tiktok', 255)->nullable();
            $table->string('social_youtube', 255)->nullable();
            $table->string('social_twitter', 255)->nullable();
            $table->string('social_facebook', 255)->nullable();
            $table->integer('followers_count')->nullable();
            $table->decimal('engagement_rate', 5, 2)->nullable();
            $table->string('niche', 100)->nullable()->comment('e.g. fashion/tech/beauty/lifestyle');
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->string('referral_code', 20)->unique();
            $table->decimal('commission_rate', 5, 2)->default(5.00)->comment('Default % of each sale they generate');
            $table->bigInteger('total_earnings_cents')->default(0);
            $table->integer('total_clicks')->default(0);
            $table->integer('total_conversions')->default(0);
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_iban', 50)->nullable();
            $table->string('bank_account_name', 255)->nullable();
            $table->char('approved_by_admin_id', 36)->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketers');
    }
};
