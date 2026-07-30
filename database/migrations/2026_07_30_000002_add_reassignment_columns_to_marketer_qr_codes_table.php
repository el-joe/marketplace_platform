<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketer_qr_codes', function (Blueprint $table) {
            $table->char('previous_marketer_id', 36)->nullable()->after('marketer_id')
                ->comment('Who this QR was previously assigned to before reassignment.');
            $table->foreign('previous_marketer_id')->references('id')->on('marketers')->nullOnDelete();

            $table->timestamp('reassigned_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('marketer_qr_codes', function (Blueprint $table) {
            $table->dropForeign(['previous_marketer_id']);
            $table->dropColumn(['previous_marketer_id', 'reassigned_at']);
        });
    }
};
