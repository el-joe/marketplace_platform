<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('country_payment_methods');
        Schema::dropIfExists('payment_methods');   // customer saved cards — no longer used
    }

    public function down(): void
    {
        // Non-reversible. Restore from backup if needed.
    }
};
