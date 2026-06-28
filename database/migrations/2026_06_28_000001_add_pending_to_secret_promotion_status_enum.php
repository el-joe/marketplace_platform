<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE marketer_secret_promotions MODIFY COLUMN status ENUM('pending','active','paused','expired') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        // Revert any pending rows to active before removing the value
        DB::statement("UPDATE marketer_secret_promotions SET status = 'active' WHERE status = 'pending'");
        DB::statement("ALTER TABLE marketer_secret_promotions MODIFY COLUMN status ENUM('active','paused','expired') NOT NULL DEFAULT 'active'");
    }
};
