<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('influencer_media_kits', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignUuid('marketer_id')->unique()->constrained('marketers');
            $table->string('headline', 255)->nullable();
            $table->string('audience_age_range', 50)->nullable();
            $table->string('audience_gender_split', 50)->nullable();
            $table->string('primary_audience_country', 100)->nullable();
            $table->unsignedInteger('avg_post_reach')->nullable();
            $table->unsignedInteger('avg_story_views')->nullable();
            $table->unsignedInteger('avg_video_views')->nullable();
            $table->bigInteger('rate_per_post')->nullable();
            $table->bigInteger('rate_per_story')->nullable();
            $table->bigInteger('rate_per_video')->nullable();
            $table->char('rate_currency', 3)->nullable();
            $table->json('portfolio_urls')->nullable()->comment('array of past campaign URLs');
            $table->json('past_brands')->nullable()->comment('array of brand name strings');
            $table->boolean('is_visible_to_vendors')->default(true);
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencer_media_kits');
    }
};
