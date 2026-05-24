<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_login_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('admin_id');
            $table->uuid('impersonating_id')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->jsonb('device_info')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->index('admin_user_id');
            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_login_sessions');
    }
};
