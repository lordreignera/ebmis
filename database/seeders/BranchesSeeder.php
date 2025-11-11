<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BranchesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Skip if branches already exist (from SQL import)
        if (DB::table('branches')->count() > 0) {
            $this->command->info('⚠️  Branches already exist. Skipping seeder.');
            return;
        }

        $branches = [
            [
                'id' => 1,
                'name' => 'Emuria Branch',
                'address' => 'Emuria Ongodia Headquarter Plot 10',
                'phone' => null,
                'email' => null,
                'manager_id' => null,
                'country_id' => 1, // Uganda
                'region_id' => null,
                'is_active' => true,
                'created_at' => '2022-03-23 09:35:35',
                'updated_at' => '2022-03-23 09:35:35'
            ],
            [
                'id' => 2,
                'name' => 'Kampala Main Branch',
                'address' => 'Kira House, Suite 17B Kampala',
                'phone' => null,
                'email' => null,
                'manager_id' => null,
                'country_id' => 1, // Uganda
                'region_id' => null,
                'is_active' => true,
                'created_at' => '2022-03-25 04:44:03',
                'updated_at' => '2022-03-25 04:44:03'
            ],
            [
                'id' => 3,
                'name' => 'Soroti Branch',
                'address' => 'Soroti town center',
                'phone' => null,
                'email' => null,
                'manager_id' => null,
                'country_id' => 1, // Uganda
                'region_id' => null,
                'is_active' => true,
                'created_at' => '2022-03-25 07:51:37',
                'updated_at' => '2022-03-25 07:51:37'
            ],
            [
                'id' => 4,
                'name' => 'City Centre Complex',
                'address' => 'Plot 12 Luwum Street, 1st Floor Room B27',
                'phone' => null,
                'email' => null,
                'manager_id' => null,
                'country_id' => 1, // Uganda
                'region_id' => null,
                'is_active' => true,
                'created_at' => '2022-05-14 05:43:29',
                'updated_at' => '2022-05-14 05:43:29'
            ],
            [
                'id' => 5,
                'name' => 'Akore Town Council Branch',
                'address' => 'Akisim Cell, Central Ward, Akore Town Council',
                'phone' => null,
                'email' => null,
                'manager_id' => null,
                'country_id' => 1, // Uganda
                'region_id' => null,
                'is_active' => true,
                'created_at' => '2022-05-17 12:58:07',
                'updated_at' => '2022-05-17 12:58:07'
            ],
            [
                'id' => 6,
                'name' => 'Ainer Kede Aswam Depository',
                'address' => 'Akworo A, Northern Ward, Akore Town Council',
                'phone' => null,
                'email' => null,
                'manager_id' => null,
                'country_id' => 1, // Uganda
                'region_id' => null,
                'is_active' => true,
                'created_at' => '2022-08-18 12:13:17',
                'updated_at' => '2022-08-18 12:13:17'
            ],
            [
                'id' => 7,
                'name' => 'Acowa Field Office',
                'address' => 'Central Ward, Acowa Town Council, Kapelebyong',
                'phone' => null,
                'email' => null,
                'manager_id' => null,
                'country_id' => 1, // Uganda
                'region_id' => null,
                'is_active' => true,
                'created_at' => '2025-10-10 02:49:44',
                'updated_at' => '2025-10-10 02:49:44'
            ],
            [
                'id' => 8,
                'name' => 'Katakwi Field Office',
                'address' => 'Central Ward',
                'phone' => null,
                'email' => null,
                'manager_id' => null,
                'country_id' => 1, // Uganda
                'region_id' => null,
                'is_active' => true,
                'created_at' => '2025-10-10 03:12:28',
                'updated_at' => '2025-10-10 03:12:28'
            ],
            [
                'id' => 9,
                'name' => 'Orungo Field Office',
                'address' => '',
                'phone' => null,
                'email' => null,
                'manager_id' => null,
                'country_id' => 1, // Uganda
                'region_id' => null,
                'is_active' => true,
                'created_at' => '2025-10-10 03:17:45',
                'updated_at' => '2025-10-10 03:17:45'
            ]
        ];

        DB::table('branches')->insert($branches);
    }
}