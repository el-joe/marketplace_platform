<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BlockTypeSeeder::class,
            AdminSeeder::class,
            CountrySeeder::class,        // currencies + countries + payment methods; sets seeder_country_ids
            CitySeeder::class,           // cities; reads seeder_country_ids
            CategoryAttributeSeeder::class, // attributes + values + categories; sets seeder_cat_ids, attr_ids, attr_value_ids
            BrandShippingSeeder::class,  // brands + carriers + methods + zones + rates; sets seeder_brand_ids
            VendorCustomerSeeder::class, // vendors + customers; sets seeder_vendor_ids
            ProductSeeder::class,        // products + variants + listings; reads all above cache keys
        ]);
    }
}
