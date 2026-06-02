<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────────────────
        // 1. ALTER TABLE marketers — add profile/boutiqaat columns
        // ─────────────────────────────────────────────────────────────────────
        Schema::table('marketers', function (Blueprint $table) {
            $table->string('profile_video_url', 500)->nullable()
                ->comment('Boutiqaat-style profile video')
                ->after('profile_photo_path');
            $table->string('profile_banner_path', 255)->nullable()
                ->after('profile_video_url');
            $table->text('promo_content')->nullable()
                ->after('profile_banner_path');
            $table->string('boutiqaat_style_slug', 100)->nullable()->unique()
                ->comment('Custom URL slug for profile page')
                ->after('promo_content');
            $table->string('whatsapp_number', 30)->nullable()
                ->after('boutiqaat_style_slug');
            $table->integer('total_samples_used')->default(0)
                ->after('whatsapp_number');
            $table->integer('total_samples_allocated')->default(0)
                ->after('total_samples_used');
        });

        // ─────────────────────────────────────────────────────────────────────
        // 2. NEW TABLE: marketer_qr_codes
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('marketer_qr_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('marketer_id', 36)->index();
            $table->foreign('marketer_id')->references('id')->on('marketers')->cascadeOnDelete();

            $table->char('campaign_id', 36)->nullable()->index();
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->nullOnDelete();

            $table->char('vendor_listing_id', 36)->nullable()->index();
            $table->foreign('vendor_listing_id')->references('id')->on('vendor_listings')->nullOnDelete();

            $table->char('product_id', 36)->nullable()->index();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();

            $table->enum('code_type', [
                'marketer_profile',
                'product',
                'vendor_store',
                'campaign',
                'whatsapp_link',
            ]);

            $table->string('qr_code_path', 255)->nullable()
                ->comment('Stored image path of the generated QR');
            $table->string('barcode_value', 500)
                ->comment('The URL encoded in the QR');
            $table->string('custom_label', 150)->nullable();
            $table->integer('scan_count')->default(0);
            $table->timestamp('last_scanned_at')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->index(['marketer_id', 'code_type']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 3. NEW TABLE: marketer_secret_promotions
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('marketer_secret_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('vendor_id', 36)->index();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();

            $table->char('vendor_listing_id', 36)->index();
            $table->foreign('vendor_listing_id')->references('id')->on('vendor_listings')->cascadeOnDelete();

            $table->char('marketer_id', 36)->nullable()->index()
                ->comment('NULL = open to any eligible marketer');
            $table->foreign('marketer_id')->references('id')->on('marketers')->nullOnDelete();

            $table->unsignedBigInteger('product_value_cents')
                ->comment('Vendor-set hidden product value');
            $table->decimal('total_commission_pct', 5, 2)
                ->comment('Total % vendor is willing to pay');
            $table->decimal('marketer_share_pct', 5, 2)
                ->comment('% that goes to marketer');
            $table->decimal('admin_share_pct', 5, 2)
                ->comment('% that goes to platform');
            $table->decimal('min_commission_pct', 5, 2)
                ->comment('Minimum floor set by admin');

            $table->tinyInteger('is_hidden_from_public')->default(1)
                ->comment('Values not shown to customer');

            $table->enum('status', ['active', 'paused', 'expired'])->default('active');
            $table->date('valid_until')->nullable();
            $table->tinyInteger('created_by_vendor')->default(1);

            $table->char('approved_by_admin_id', 36)->nullable()->index();
            $table->foreign('approved_by_admin_id')->references('id')->on('admins')->nullOnDelete();

            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['marketer_id', 'status']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 4. NEW TABLE: marketer_commission_tiers
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('marketer_commission_tiers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('marketer_id', 36)->index();
            $table->foreign('marketer_id')->references('id')->on('marketers')->cascadeOnDelete();

            $table->char('campaign_id', 36)->nullable()->index()
                ->comment('NULL = applies to all campaigns for this marketer');
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->nullOnDelete();

            $table->integer('tier_order')
                ->comment('1=first tier, 2=second, 3=third');
            $table->integer('min_sales_count')
                ->comment('Sales needed to unlock this tier');
            $table->integer('max_sales_count')->nullable()
                ->comment('NULL = unlimited (top tier)');

            $table->decimal('commission_rate', 5, 2);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->unique(['marketer_id', 'campaign_id', 'tier_order'], 'unique_marketer_tier');
        });

        // ─────────────────────────────────────────────────────────────────────
        // 5. NEW TABLE: marketer_whatsapp_links
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('marketer_whatsapp_links', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('marketer_id', 36)->index();
            $table->foreign('marketer_id')->references('id')->on('marketers')->cascadeOnDelete();

            $table->char('campaign_id', 36)->nullable()->index();
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->nullOnDelete();

            $table->enum('link_type', ['discount', 'free_shipping', 'both', 'custom']);

            $table->decimal('discount_pct', 5, 2)->nullable()
                ->comment('% off for customer who uses this link');
            $table->tinyInteger('free_shipping')->default(0);

            $table->string('coupon_code', 50)->nullable()->index()
                ->comment('Auto-generated coupon linked to this share');
            $table->foreign('coupon_code')->references('code')->on('coupons')->nullOnDelete();

            $table->text('whatsapp_message_template')->nullable()
                ->comment('Template with {{link}} placeholder');
            $table->string('tracking_url', 500)
                ->comment('Full trackable URL');
            $table->string('qr_code_path', 255)->nullable();

            $table->integer('share_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->integer('conversion_count')->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['marketer_id', 'is_active']);
        });

        // ─────────────────────────────────────────────────────────────────────
        // 6. NEW TABLES: marketer_sample_requests + marketer_sample_items
        // ─────────────────────────────────────────────────────────────────────
        Schema::create('marketer_sample_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('marketer_id', 36)->index();
            $table->foreign('marketer_id')->references('id')->on('marketers')->cascadeOnDelete();

            $table->char('vendor_id', 36)->index();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete();

            $table->char('campaign_id', 36)->nullable()->index();
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->nullOnDelete();

            $table->enum('status', [
                'requested',
                'approved',
                'dispatched',
                'received',
                'rejected',
            ])->default('requested');

            $table->text('notes')->nullable();

            $table->char('admin_approved_by', 36)->nullable()->index();
            $table->foreign('admin_approved_by')->references('id')->on('admins')->nullOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['marketer_id', 'status']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('marketer_sample_items', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->char('sample_request_id', 36)->index();
            $table->foreign('sample_request_id')->references('id')->on('marketer_sample_requests')->cascadeOnDelete();

            $table->char('vendor_listing_id', 36)->index();
            $table->foreign('vendor_listing_id')->references('id')->on('vendor_listings')->cascadeOnDelete();

            $table->integer('quantity')->default(1);
            $table->tinyInteger('is_mandatory')->default(1)
                ->comment('Mandatory platform samples = non-refundable');
            $table->unsignedBigInteger('sample_cost_cents')->default(0)
                ->comment('Cost borne by vendor (marketing expense)');

            $table->timestamp('created_at')->nullable();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 7. ALTER TABLE marketer_campaigns — add new columns
        // ─────────────────────────────────────────────────────────────────────
        Schema::table('marketer_campaigns', function (Blueprint $table) {
            $table->timestamp('auto_approve_at')->nullable()
                ->comment('If admin does not act by this time, auto-approve')
                ->after('approved_at');
            $table->tinyInteger('auto_approved')->default(0)
                ->after('auto_approve_at');
            $table->enum('attribution_model', ['last_click', 'first_click', 'linear'])
                ->default('last_click')
                ->after('auto_approved');
            $table->tinyInteger('whatsapp_sharing_enabled')->default(0)
                ->after('attribution_model');
            $table->integer('samples_required')->default(0)
                ->comment('Admin-set mandatory samples count')
                ->after('whatsapp_sharing_enabled');
            $table->char('secret_promotion_id', 36)->nullable()->index()
                ->after('samples_required');
            $table->foreign('secret_promotion_id')
                ->references('id')->on('marketer_secret_promotions')
                ->nullOnDelete();
        });

        // ─────────────────────────────────────────────────────────────────────
        // 8. ALTER TABLE marketer_conversions — add attribution columns
        // ─────────────────────────────────────────────────────────────────────
        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->enum('attribution_model', ['last_click', 'first_click', 'linear'])
                ->default('last_click')
                ->after('reversal_reason');
            $table->json('click_chain')->nullable()
                ->comment('Array of click_ids for multi-touch attribution')
                ->after('attribution_model');
            $table->tinyInteger('is_whatsapp_conversion')->default(0)
                ->after('click_chain');
            $table->char('whatsapp_link_id', 36)->nullable()->index()
                ->after('is_whatsapp_conversion');
            $table->foreign('whatsapp_link_id')
                ->references('id')->on('marketer_whatsapp_links')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        // ── Drop new columns on marketer_conversions ──────────────────────────
        Schema::table('marketer_conversions', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_link_id']);
            $table->dropColumn(['attribution_model', 'click_chain', 'is_whatsapp_conversion', 'whatsapp_link_id']);
        });

        // ── Drop new columns on marketer_campaigns ────────────────────────────
        Schema::table('marketer_campaigns', function (Blueprint $table) {
            $table->dropForeign(['secret_promotion_id']);
            $table->dropColumn(['auto_approve_at', 'auto_approved', 'attribution_model', 'whatsapp_sharing_enabled', 'samples_required', 'secret_promotion_id']);
        });

        // ── Drop new tables (reverse dependency order) ────────────────────────
        Schema::dropIfExists('marketer_sample_items');
        Schema::dropIfExists('marketer_sample_requests');
        Schema::dropIfExists('marketer_whatsapp_links');
        Schema::dropIfExists('marketer_commission_tiers');
        Schema::dropIfExists('marketer_secret_promotions');
        Schema::dropIfExists('marketer_qr_codes');

        // ── Drop new columns on marketers ─────────────────────────────────────
        Schema::table('marketers', function (Blueprint $table) {
            $table->dropUnique(['boutiqaat_style_slug']);
            $table->dropColumn([
                'profile_video_url',
                'profile_banner_path',
                'promo_content',
                'boutiqaat_style_slug',
                'whatsapp_number',
                'total_samples_used',
                'total_samples_allocated',
            ]);
        });
    }
};
