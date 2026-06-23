<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // vendor_listings: fulfillment system type + global shipping + subscription plan
        Schema::table('vendor_listings', function (Blueprint $table) {
            $table->enum('global_system_type', ['express_fbn', 'merchant_fbp', 'marketplace'])
                ->default('merchant_fbp')
                ->after('fulfillment_model');
            $table->tinyInteger('is_global_shipping')
                ->default(0)
                ->comment('النظام العالمي')
                ->after('global_system_type');
            $table->char('subscription_plan_id', 36)
                ->nullable()
                ->comment('Active subscription for this listing')
                ->after('is_global_shipping');
            $table->foreign('subscription_plan_id')
                ->references('id')
                ->on('subscription_plans')
                ->nullOnDelete();
        });

        // orders: marketer referral columns
        Schema::table('orders', function (Blueprint $table) {
            $table->char('marketer_id', 36)
                ->nullable()
                ->comment('Marketer who referred this order')
                ->after('cancelled_at');
            $table->foreign('marketer_id')
                ->references('id')
                ->on('marketers')
                ->nullOnDelete();
            $table->char('marketer_campaign_id', 36)
                ->nullable()
                ->after('marketer_id');
            $table->foreign('marketer_campaign_id')
                ->references('id')
                ->on('marketer_campaigns')
                ->nullOnDelete();
        });

        // delivery_agents: zone + extra profile columns
        Schema::table('delivery_agents', function (Blueprint $table) {
            $table->char('zone_id', 36)
                ->nullable()
                ->comment('Primary delivery zone')
                ->after('agent_type');
            $table->foreign('zone_id')
                ->references('id')
                ->on('delivery_zones')
                ->nullOnDelete();
            $table->string('national_id', 30)->nullable()->after('zone_id');
            $table->string('vehicle_plate', 20)->nullable()->after('national_id');
            $table->string('emergency_contact_name', 255)->nullable()->after('vehicle_plate');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');
            $table->bigInteger('base_salary_cents')
                ->default(0)
                ->comment('Monthly base if salaried')
                ->after('emergency_contact_phone');
            $table->bigInteger('per_delivery_fee_cents')
                ->default(500)
                ->comment('Fee per completed delivery (500 = 5 EGP)')
                ->after('base_salary_cents');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_agents', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn([
                'zone_id',
                'national_id',
                'vehicle_plate',
                'emergency_contact_name',
                'emergency_contact_phone',
                'base_salary_cents',
                'per_delivery_fee_cents',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['marketer_id']);
            $table->dropForeign(['marketer_campaign_id']);
            $table->dropColumn(['marketer_id', 'marketer_campaign_id']);
        });

        Schema::table('vendor_listings', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn(['global_system_type', 'is_global_shipping', 'subscription_plan_id']);
        });
    }
};
