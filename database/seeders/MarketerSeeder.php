<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Marketer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds marketer accounts on the 'marketer' guard (marketers table).
 * Covers all four marketer types (influencer/celebrity/affiliate/brand_ambassador)
 * and all four status states (active/pending/rejected/suspended) so every admin
 * panel filter and queue has realistic demo data.
 *
 * All accounts use password: password123
 * Fully idempotent — keyed on email with firstOrCreate().
 */
class MarketerSeeder extends Seeder
{
    public function run(): void
    {
        $marketersData = [
            [
                'name'            => 'Yasmin Style',
                'email'           => 'yasmin@marketer.com',
                'type'            => 'influencer',
                'country_iso'     => 'AE',
                'niche'           => 'fashion',
                'followers_count' => 250000,
                'engagement_rate' => 4.2,
                'commission_rate' => 8.00,
                'status'          => 'active',
            ],
            [
                'name'            => 'Omar The Tech Guy',
                'email'           => 'omar@marketer.com',
                'type'            => 'influencer',
                'country_iso'     => 'SA',
                'niche'           => 'technology',
                'followers_count' => 180000,
                'engagement_rate' => 5.1,
                'commission_rate' => 10.00,
                'status'          => 'active',
            ],
            [
                'name'            => 'Celebrity Chef Hana',
                'email'           => 'hana@marketer.com',
                'type'            => 'celebrity',
                'country_iso'     => 'EG',
                'niche'           => 'food_lifestyle',
                'followers_count' => 1200000,
                'engagement_rate' => 6.8,
                'commission_rate' => 15.00,
                'status'          => 'active',
            ],
            [
                'name'            => 'Budget Deals Affiliate',
                'email'           => 'budgetdeals@marketer.com',
                'type'            => 'affiliate',
                'country_iso'     => 'KW',
                'niche'           => 'general',
                'followers_count' => 15000,
                'engagement_rate' => 2.1,
                'commission_rate' => 5.00,
                'status'          => 'active',
            ],
            [
                'name'            => 'New Applicant Sara',
                'email'           => 'pending-marketer@marketer.com',
                'type'            => 'influencer',
                'country_iso'     => 'AE',
                'niche'           => 'beauty',
                'followers_count' => 45000,
                'engagement_rate' => 3.9,
                'commission_rate' => 7.00,
                'status'          => 'pending', // approval-queue demo
            ],
            [
                'name'            => 'Rejected Account',
                'email'           => 'rejected-marketer@marketer.com',
                'type'            => 'affiliate',
                'country_iso'     => 'EG',
                'niche'           => 'general',
                'followers_count' => 500,
                'engagement_rate' => 0.5,
                'commission_rate' => 5.00,
                'status'          => 'rejected',
            ],
            [
                'name'            => 'Suspended Influencer',
                'email'           => 'suspended-marketer@marketer.com',
                'type'            => 'brand_ambassador',
                'country_iso'     => 'SA',
                'niche'           => 'fitness',
                'followers_count' => 90000,
                'engagement_rate' => 4.0,
                'commission_rate' => 9.00,
                'status'          => 'suspended',
            ],
        ];

        foreach ($marketersData as $data) {
            $country = Country::where('iso_code_2', $data['country_iso'])->first();

            $marketer = Marketer::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'                  => $data['name'],
                    'password'              => Hash::make('password123'),
                    'type'                  => $data['type'],
                    'country_id'            => $country?->id,
                    'niche'                 => $data['niche'],
                    'followers_count'       => $data['followers_count'],
                    'engagement_rate'       => $data['engagement_rate'],
                    'commission_rate'       => $data['commission_rate'],
                    'status'                => $data['status'],
                    'referral_code'         => 'MKT-' . Str::upper(Str::random(6)),
                    'boutiqaat_style_slug'  => Str::slug($data['name']),
                    'bio'                   => fake()->paragraph(),
                    'total_clicks'          => fake()->numberBetween(0, 10000),
                    'total_conversions'     => fake()->numberBetween(0, 300),
                    'total_earnings'  => fake()->numberBetween(0, 500000),
                    'approved_at'           => $data['status'] === 'active' ? now() : null,
                ]
            );

            $this->command->line("  ✓ Marketer: {$data['name']} ({$data['type']}, {$data['status']})");
        }

        $this->command->info('✅ Marketers seeded (' . count($marketersData) . ' accounts — all 4 types, all 4 statuses).');
    }
}
