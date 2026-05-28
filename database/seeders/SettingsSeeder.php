<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            // ─── General ──────────────────────────────────────────────────────
            ['category' => 'general', 'key' => 'site_name', 'value' => json_encode('Marketplace'), 'description' => 'The public name of the platform.'],
            ['category' => 'general', 'key' => 'site_email', 'value' => json_encode('hello@example.com'), 'description' => 'Primary contact / from email address.'],
            ['category' => 'general', 'key' => 'support_phone', 'value' => json_encode('+1-800-000-0000'), 'description' => 'Customer-facing support phone number.'],
            ['category' => 'general', 'key' => 'default_country', 'value' => json_encode('US'), 'description' => 'ISO 3166-1 alpha-2 code for the default country.'],

            // ─── Orders ───────────────────────────────────────────────────────
            ['category' => 'orders', 'key' => 'min_order_amount', 'value' => json_encode(500), 'description' => 'Minimum order amount in base currency cents (e.g. 500 = $5.00).'],
            ['category' => 'orders', 'key' => 'max_cart_items', 'value' => json_encode(50), 'description' => 'Maximum number of distinct line items allowed in a cart.'],
            ['category' => 'orders', 'key' => 'sla_ship_hours', 'value' => json_encode(48), 'description' => 'Hours vendors have to ship an order before an SLA breach.'],
            ['category' => 'orders', 'key' => 'return_window_days', 'value' => json_encode(14), 'description' => 'Number of days a customer has to initiate a return.'],
            ['category' => 'orders', 'key' => 'max_return_items', 'value' => json_encode(10), 'description' => 'Maximum number of items that can be included in a single return request.'],

            // ─── Finance ──────────────────────────────────────────────────────
            ['category' => 'finance', 'key' => 'minimum_payout_amount', 'value' => json_encode(5000), 'description' => 'Minimum balance (in cents) before a vendor payout is generated.'],
            ['category' => 'finance', 'key' => 'platform_commission_default', 'value' => json_encode(10.0), 'description' => 'Default platform commission percentage (e.g. 10 = 10%).'],
            ['category' => 'finance', 'key' => 'payout_day_of_week', 'value' => json_encode(1), 'description' => 'Day of week for weekly payouts (0=Sunday, 1=Monday … 6=Saturday).'],
            ['category' => 'finance', 'key' => 'payout_day_of_month', 'value' => json_encode(1), 'description' => 'Day of month for monthly payouts (1–28).'],

            // ─── Customers ────────────────────────────────────────────────────
            ['category' => 'customers', 'key' => 'loyalty_points_per_order_egp', 'value' => json_encode(1), 'description' => 'Loyalty points awarded per EGP spent on an order.'],
            ['category' => 'customers', 'key' => 'referral_bonus_points', 'value' => json_encode(100), 'description' => 'Points awarded to referrer when a referred customer completes their first order.'],
            ['category' => 'customers', 'key' => 'max_addresses_per_customer', 'value' => json_encode(10), 'description' => 'Maximum number of saved addresses per customer account.'],

            // ─── Notifications ────────────────────────────────────────────────
            ['category' => 'notifications', 'key' => 'admin_email_new_order', 'value' => json_encode(true), 'description' => 'Send admin email notification when a new order is placed.'],
            ['category' => 'notifications', 'key' => 'admin_email_new_vendor', 'value' => json_encode(true), 'description' => 'Send admin email notification when a new vendor application is submitted.'],
            ['category' => 'notifications', 'key' => 'admin_email_new_dispute', 'value' => json_encode(true), 'description' => 'Send admin email notification when a new dispute is opened.'],
            ['category' => 'notifications', 'key' => 'admin_email_sla_breach', 'value' => json_encode(true), 'description' => 'Send admin email notification when an SLA breach is detected.'],

            // ─── Integrations ─────────────────────────────────────────────────
            ['category' => 'integrations', 'key' => 'payment_gateway_live_mode', 'value' => json_encode(false), 'description' => 'Enable live (production) mode for the payment gateway. Set false for sandbox.', 'is_encrypted' => 0],
            ['category' => 'integrations', 'key' => 'default_shipping_carrier', 'value' => json_encode('aramex'), 'description' => 'Default shipping carrier code used for rate lookups.'],

            // ─── Security ─────────────────────────────────────────────────────
            ['category' => 'security', 'key' => 'max_login_attempts', 'value' => json_encode(5), 'description' => 'Maximum failed login attempts before lockout.'],
            ['category' => 'security', 'key' => 'lockout_minutes', 'value' => json_encode(15), 'description' => 'Minutes an account is locked after exceeding max login attempts.'],
            ['category' => 'security', 'key' => 'require_2fa_admins', 'value' => json_encode(false), 'description' => 'Require two-factor authentication for all admin accounts.'],
        ];

        $now = now();

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge([
                    'id' => (string) Str::uuid(),
                    'is_encrypted' => 0,
                    'is_public' => 0,
                    'updated_at' => $now,
                ], $setting)
            );
        }
    }
}
