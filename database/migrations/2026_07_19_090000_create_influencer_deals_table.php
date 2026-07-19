<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('influencer_deals', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->foreignUuid('marketer_id')->constrained('marketers');
            $table->foreignUuid('vendor_id')->nullable()->constrained('vendors');
            $table->char('campaign_id', 36)->nullable()->index();
            $table->foreign('campaign_id')->references('id')->on('marketer_campaigns')->nullOnDelete();
            $table->string('deal_name', 200);
            $table->text('description')->nullable();
            $table->enum('deal_type', ['flat_fee', 'hybrid', 'gifting'])->default('flat_fee')
                ->comment('flat_fee: fixed payment only; hybrid: flat_fee + commission on sales generated; gifting: product samples only, no cash');
            $table->bigInteger('flat_fee_amount')->default(0);
            $table->char('currency', 3);
            $table->decimal('hybrid_commission_rate', 5, 2)->nullable()->comment('only when deal_type = hybrid');
            $table->enum('status', [
                'proposed', 'negotiating', 'accepted',
                'in_progress', 'content_submitted',
                'approved', 'paid', 'cancelled', 'rejected',
            ])->default('proposed');
            $table->enum('proposed_by', ['admin', 'vendor', 'marketer']);
            $table->text('negotiation_notes')->nullable();
            $table->timestamp('contract_signed_at')->nullable();
            $table->timestamp('content_due_at')->nullable();
            $table->timestamp('payment_due_at')->nullable();
            $table->foreignUuid('approved_by_admin_id')->nullable()->constrained('admins');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index(['marketer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('influencer_deals');
    }
};
