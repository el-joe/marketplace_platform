<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_listings', function (Blueprint $table) {
            $table->decimal('influencer_commission_percentage', 5, 2)->nullable()
                ->comment('Commission % vendor offers influencers to promote this listing; null = not opted in');
            $table->decimal('affiliate_commission_percentage', 5, 2)->nullable()
                ->comment('Commission % vendor offers affiliates/marketers to promote this listing; null = not opted in');
            $table->unsignedSmallInteger('influencer_sample_quota')->nullable()
                ->comment('Free samples vendor allocates for influencer promotion');
            $table->unsignedSmallInteger('affiliate_sample_quota')->nullable()
                ->comment('Free samples vendor allocates for affiliate promotion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_listings', function (Blueprint $table) {
            $table->dropColumn([
                'influencer_commission_percentage',
                'affiliate_commission_percentage',
                'influencer_sample_quota',
                'affiliate_sample_quota',
            ]);
        });
    }
};
