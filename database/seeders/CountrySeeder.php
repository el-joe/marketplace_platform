<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // ── Currencies ─────────────────────────────────────────────────────
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'exchange_rate_to_base' => 1.000000],
            ['code' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => '﷼', 'decimal_places' => 2, 'exchange_rate_to_base' => 3.750000],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'decimal_places' => 2, 'exchange_rate_to_base' => 3.673000],
            ['code' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => 'E£', 'decimal_places' => 2, 'exchange_rate_to_base' => 48.500000],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'symbol' => 'KD', 'decimal_places' => 3, 'exchange_rate_to_base' => 0.307000],
            ['code' => 'QAR', 'name' => 'Qatari Riyal', 'symbol' => 'QR', 'decimal_places' => 2, 'exchange_rate_to_base' => 3.640000],
            ['code' => 'OMR', 'name' => 'Omani Rial', 'symbol' => 'RO', 'decimal_places' => 3, 'exchange_rate_to_base' => 0.385000],
            ['code' => 'BHD', 'name' => 'Bahraini Dinar', 'symbol' => 'BD', 'decimal_places' => 3, 'exchange_rate_to_base' => 0.376000],
            ['code' => 'JOD', 'name' => 'Jordanian Dinar', 'symbol' => 'JD', 'decimal_places' => 3, 'exchange_rate_to_base' => 0.709000],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'exchange_rate_to_base' => 0.920000],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_places' => 2, 'exchange_rate_to_base' => 0.790000],
        ];

        foreach ($currencies as $c) {
            DB::table('currencies')->upsert(
                array_merge($c, [
                    'base_currency_code' => 'USD',
                    'is_active' => true,
                    'is_manually_overridden' => false,
                    'rate_updated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
                ['code'],
                ['name', 'symbol', 'decimal_places', 'exchange_rate_to_base', 'rate_updated_at', 'updated_at']
            );
        }

        // ── Countries ──────────────────────────────────────────────────────
        $countries = [
            [
                'iso_code_2' => 'SA',
                'iso_code_3' => 'SAU',
                'site_code' => 'ksa',
                'site_domain' => 'sa.noon.com',
                'name_en' => 'Saudi Arabia',
                'name_ar' => 'المملكة العربية السعودية',
                'phone_prefix' => '+966',
                'currency_code' => 'SAR',
                'vat_rate' => 15.00,
                'default_locale' => 'ar',
                'timezone' => 'Asia/Riyadh',
                'is_active' => true,
                'is_launched' => true,
                'cod_available' => true,
                'launched_at' => now()->subYears(3),
            ],
            [
                'iso_code_2' => 'AE',
                'iso_code_3' => 'ARE',
                'site_code' => 'uae',
                'site_domain' => 'ae.noon.com',
                'name_en' => 'United Arab Emirates',
                'name_ar' => 'الإمارات العربية المتحدة',
                'phone_prefix' => '+971',
                'currency_code' => 'AED',
                'vat_rate' => 5.00,
                'default_locale' => 'ar',
                'timezone' => 'Asia/Dubai',
                'is_active' => true,
                'is_launched' => true,
                'cod_available' => true,
                'launched_at' => now()->subYears(4),
            ],
            [
                'iso_code_2' => 'EG',
                'iso_code_3' => 'EGY',
                'site_code' => 'egy',
                'site_domain' => 'eg.noon.com',
                'name_en' => 'Egypt',
                'name_ar' => 'مصر',
                'phone_prefix' => '+20',
                'currency_code' => 'EGP',
                'vat_rate' => 14.00,
                'default_locale' => 'ar',
                'timezone' => 'Africa/Cairo',
                'is_active' => true,
                'is_launched' => true,
                'cod_available' => true,
                'launched_at' => now()->subYears(2),
            ],
            [
                'iso_code_2' => 'KW',
                'iso_code_3' => 'KWT',
                'site_code' => 'kwt',
                'site_domain' => 'kw.noon.com',
                'name_en' => 'Kuwait',
                'name_ar' => 'الكويت',
                'phone_prefix' => '+965',
                'currency_code' => 'KWD',
                'vat_rate' => 0.00,
                'default_locale' => 'ar',
                'timezone' => 'Asia/Kuwait',
                'is_active' => true,
                'is_launched' => true,
                'cod_available' => true,
                'launched_at' => now()->subYear(),
            ],
            [
                'iso_code_2' => 'QA',
                'iso_code_3' => 'QAT',
                'site_code' => 'qat',
                'site_domain' => 'qa.noon.com',
                'name_en' => 'Qatar',
                'name_ar' => 'قطر',
                'phone_prefix' => '+974',
                'currency_code' => 'QAR',
                'vat_rate' => 0.00,
                'default_locale' => 'ar',
                'timezone' => 'Asia/Qatar',
                'is_active' => true,
                'is_launched' => false,
                'cod_available' => false,
                'launched_at' => null,
            ],
            [
                'iso_code_2' => 'OM',
                'iso_code_3' => 'OMN',
                'site_code' => 'omn',
                'site_domain' => 'om.noon.com',
                'name_en' => 'Oman',
                'name_ar' => 'عُمان',
                'phone_prefix' => '+968',
                'currency_code' => 'OMR',
                'vat_rate' => 5.00,
                'default_locale' => 'ar',
                'timezone' => 'Asia/Muscat',
                'is_active' => true,
                'is_launched' => false,
                'cod_available' => false,
                'launched_at' => null,
            ],
            [
                'iso_code_2' => 'BH',
                'iso_code_3' => 'BHR',
                'site_code' => 'bhr',
                'site_domain' => 'bh.noon.com',
                'name_en' => 'Bahrain',
                'name_ar' => 'البحرين',
                'phone_prefix' => '+973',
                'currency_code' => 'BHD',
                'vat_rate' => 10.00,
                'default_locale' => 'ar',
                'timezone' => 'Asia/Bahrain',
                'is_active' => true,
                'is_launched' => false,
                'cod_available' => false,
                'launched_at' => null,
            ],
            [
                'iso_code_2' => 'JO',
                'iso_code_3' => 'JOR',
                'site_code' => 'jor',
                'site_domain' => 'jo.noon.com',
                'name_en' => 'Jordan',
                'name_ar' => 'الأردن',
                'phone_prefix' => '+962',
                'currency_code' => 'JOD',
                'vat_rate' => 16.00,
                'default_locale' => 'ar',
                'timezone' => 'Asia/Amman',
                'is_active' => true,
                'is_launched' => false,
                'cod_available' => false,
                'launched_at' => null,
            ],
        ];

        $countryIds = [];
        foreach ($countries as $c) {
            $existing = DB::table('countries')->where('iso_code_2', $c['iso_code_2'])->first();
            if ($existing) {
                $countryIds[$c['iso_code_2']] = $existing->id;
                DB::table('countries')->where('id', $existing->id)->update(array_merge($c, ['updated_at' => now()]));
                continue;
            }
            $id = Str::uuid()->toString();
            $countryIds[$c['iso_code_2']] = $id;
            DB::table('countries')->insert(array_merge($c, [
                'id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // Store IDs in a cache for downstream seeders
        cache()->put('seeder_country_ids', $countryIds, 600);

        // ── Country Payment Methods ─────────────────────────────────────────
        $launchedCountries = ['SA', 'AE', 'EG', 'KW'];
        foreach ($launchedCountries as $iso) {
            $cid = $countryIds[$iso] ?? null;
            if (!$cid)
                continue;

            $methods = [
                ['method_type' => 'card', 'provider' => 'paytabs', 'display_name_en' => 'Credit / Debit Card', 'display_name_ar' => 'بطاقة ائتمان / خصم', 'fee_pct' => 1.50, 'sort_order' => 1],
                ['method_type' => 'wallet', 'provider' => 'noon_pay', 'display_name_en' => 'Noon Pay Wallet', 'display_name_ar' => 'محفظة نون باي', 'fee_pct' => 0.00, 'sort_order' => 2],
                ['method_type' => 'cod', 'provider' => null, 'display_name_en' => 'Cash on Delivery', 'display_name_ar' => 'الدفع عند الاستلام', 'fee_pct' => 0.00, 'fee_fixed' => 100, 'sort_order' => 3],
                ['method_type' => 'bank_transfer', 'provider' => null, 'display_name_en' => 'Bank Transfer', 'display_name_ar' => 'تحويل بنكي', 'fee_pct' => 0.00, 'sort_order' => 4],
            ];

            // BNPL only for SA/AE
            if (in_array($iso, ['SA', 'AE'])) {
                $methods[] = ['method_type' => 'bnpl', 'provider' => 'tabby', 'display_name_en' => 'Pay Later (Tabby)', 'display_name_ar' => 'تابي - اشتري الآن وادفع لاحقاً', 'fee_pct' => 0.00, 'sort_order' => 5];
            }

            foreach ($methods as $m) {
                $exists = DB::table('country_payment_methods')
                    ->where('country_id', $cid)
                    ->where('method_type', $m['method_type'])
                    ->where('provider', $m['provider'] ?? null)
                    ->exists();
                if (!$exists) {
                    DB::table('country_payment_methods')->insert(array_merge([
                        'id' => Str::uuid(),
                        'country_id' => $cid,
                        'fee_pct' => 0.00,
                        'fee_fixed' => 0,
                        'min_order' => 0,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ], $m));
                }
            }
        }
    }
}
