<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_request_message_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('return_request_message_id')->constrained('return_request_messages')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('return_request_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_request_message_attachments');
    }
};
