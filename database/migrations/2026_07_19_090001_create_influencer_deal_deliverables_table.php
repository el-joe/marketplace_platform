<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('influencer_deal_deliverables', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('deal_id', 36)->index();
            $table->foreign('deal_id')->references('id')->on('influencer_deals')->cascadeOnDelete();
            $table->enum('platform', [
                'instagram_post', 'instagram_story', 'instagram_reel',
                'tiktok', 'youtube', 'twitter', 'facebook', 'blog', 'other',
            ]);
            $table->enum('content_type', [
                'post', 'story', 'reel', 'video', 'review',
                'unboxing', 'tutorial', 'other',
            ]);
            $table->text('description')->nullable();
            $table->string('content_url', 500)->nullable();
            $table->text('content_notes')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('approved_by_admin_id')->nullable()->constrained('admins');
            $table->enum('status', ['pending', 'submitted', 'approved', 'rejected', 'overdue'])
                ->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencer_deal_deliverables');
    }
};
