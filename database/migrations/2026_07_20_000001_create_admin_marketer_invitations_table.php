<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_marketer_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('admin_id')
                ->constrained('admins')->cascadeOnDelete();
            $table->foreignUuid('marketer_id')
                ->constrained('marketers')->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('description')->nullable();

            // Same enum as marketer_campaigns.campaign_type (App\Enums\CampaignType) —
            // the accepted offer becomes a marketer_campaigns row directly.
            $table->enum('campaign_type', [
                'product_promotion',
                'store_promotion',
                'category_promotion',
                'brand_deal',
                'product_specific',
                'flash_sale',
                'general',
                'classified_promotion',
                'travel_promotion',
                'referral_link',
                'discount_code',
            ]);

            $table->bigInteger('offered_commission_rate')
                ->comment('Basis points — e.g. 500 = 5.00%');
            $table->enum('commission_type', ['percentage', 'flat_per_conversion', 'flat_per_click'])
                ->default('percentage');

            $table->bigInteger('budget')->nullable();
            $table->char('budget_currency', 3)->nullable();

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('expires_at')->nullable()
                ->comment('Offer auto-expires if marketer does not respond by this time');

            $table->enum('status', ['pending', 'accepted', 'declined', 'expired', 'revoked'])
                ->default('pending');

            $table->text('marketer_note')->nullable()
                ->comment('Marketer response note on accept/decline');
            $table->timestamp('responded_at')->nullable();

            $table->foreignUuid('resulting_campaign_id')->nullable()
                ->constrained('marketer_campaigns')->nullOnDelete();

            $table->timestamps();

            $table->index(['marketer_id', 'status'], 'admin_marketer_invitations_marketer_status_index');
            $table->index(['admin_id', 'status'], 'admin_marketer_invitations_admin_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_marketer_invitations');
    }
};
