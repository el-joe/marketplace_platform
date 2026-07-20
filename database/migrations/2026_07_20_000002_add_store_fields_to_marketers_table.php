<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            $table->string('display_name', 255)->nullable()->after('name');
            $table->text('bio_ar')->nullable()->after('bio');
            $table->string('website_url', 500)->nullable()->after('profile_video_url');
            $table->string('social_snapchat', 255)->nullable()->after('social_facebook');
            $table->boolean('is_profile_public')->default(true)->after('boutiqaat_style_slug');
            $table->boolean('accept_new_campaigns')->default(true)->after('is_profile_public');
        });
    }

    public function down(): void
    {
        Schema::table('marketers', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'bio_ar',
                'website_url',
                'social_snapchat',
                'is_profile_public',
                'accept_new_campaigns',
            ]);
        });
    }
};
