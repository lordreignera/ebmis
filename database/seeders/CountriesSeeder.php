<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Country;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'Uganda', 'code' => 'UG', 'is_active' => true],
            ['name' => 'Kenya', 'code' => 'KE', 'is_active' => true],
            ['name' => 'Tanzania', 'code' => 'TZ', 'is_active' => true],
            ['name' => 'Rwanda', 'code' => 'RW', 'is_active' => true],
            ['name' => 'Burundi', 'code' => 'BI', 'is_active' => true],
            ['name' => 'South Sudan', 'code' => 'SS', 'is_active' => true],
            ['name' => 'Democratic Republic of Congo', 'code' => 'CD', 'is_active' => true],
            ['name' => 'Ethiopia', 'code' => 'ET', 'is_active' => true],
            ['name' => 'Somalia', 'code' => 'SO', 'is_active' => true],
            ['name' => 'Sudan', 'code' => 'SD', 'is_active' => true],
            ['name' => 'United States', 'code' => 'US', 'is_active' => true],
            ['name' => 'United Kingdom', 'code' => 'GB', 'is_active' => true],
            ['name' => 'Canada', 'code' => 'CA', 'is_active' => true],
            ['name' => 'Germany', 'code' => 'DE', 'is_active' => true],
            ['name' => 'France', 'code' => 'FR', 'is_active' => true],
            ['name' => 'Netherlands', 'code' => 'NL', 'is_active' => true],
            ['name' => 'Sweden', 'code' => 'SE', 'is_active' => true],
            ['name' => 'Norway', 'code' => 'NO', 'is_active' => true],
            ['name' => 'Denmark', 'code' => 'DK', 'is_active' => true],
            ['name' => 'Australia', 'code' => 'AU', 'is_active' => true],
            ['name' => 'South Africa', 'code' => 'ZA', 'is_active' => true],
            ['name' => 'Nigeria', 'code' => 'NG', 'is_active' => true],
            ['name' => 'Ghana', 'code' => 'GH', 'is_active' => true],
            ['name' => 'India', 'code' => 'IN', 'is_active' => true],
            ['name' => 'China', 'code' => 'CN', 'is_active' => true],
            ['name' => 'Japan', 'code' => 'JP', 'is_active' => true],
            ['name' => 'Brazil', 'code' => 'BR', 'is_active' => true],
            ['name' => 'Other', 'code' => 'XX', 'is_active' => true],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['code' => $country['code']],
                $country
            );
        }
    }
}
