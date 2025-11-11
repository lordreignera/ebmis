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
        // Check if countries table already has data (from imported SQL)
        if (Country::count() > 0) {
            $this->command->info('Countries already seeded from SQL import. Skipping...');
            return;
        }

        $countries = [
            ['name' => 'Uganda', 'code' => 'UG'],
            ['name' => 'Kenya', 'code' => 'KE'],
            ['name' => 'Tanzania', 'code' => 'TZ'],
            ['name' => 'Rwanda', 'code' => 'RW'],
            ['name' => 'Burundi', 'code' => 'BI'],
            ['name' => 'South Sudan', 'code' => 'SS'],
            ['name' => 'Democratic Republic of Congo', 'code' => 'CD'],
            ['name' => 'Ethiopia', 'code' => 'ET'],
            ['name' => 'Somalia', 'code' => 'SO'],
            ['name' => 'Sudan', 'code' => 'SD'],
            ['name' => 'United States', 'code' => 'US'],
            ['name' => 'United Kingdom', 'code' => 'GB'],
            ['name' => 'Canada', 'code' => 'CA'],
            ['name' => 'Germany', 'code' => 'DE'],
            ['name' => 'France', 'code' => 'FR'],
            ['name' => 'Netherlands', 'code' => 'NL'],
            ['name' => 'Sweden', 'code' => 'SE'],
            ['name' => 'Norway', 'code' => 'NO'],
            ['name' => 'Denmark', 'code' => 'DK'],
            ['name' => 'Australia', 'code' => 'AU'],
            ['name' => 'South Africa', 'code' => 'ZA'],
            ['name' => 'Nigeria', 'code' => 'NG'],
            ['name' => 'Ghana', 'code' => 'GH'],
            ['name' => 'India', 'code' => 'IN'],
            ['name' => 'China', 'code' => 'CN'],
            ['name' => 'Japan', 'code' => 'JP'],
            ['name' => 'Brazil', 'code' => 'BR'],
            ['name' => 'Other', 'code' => 'XX'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['code' => $country['code']],
                $country
            );
        }
    }
}
