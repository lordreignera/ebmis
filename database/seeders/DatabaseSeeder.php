<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->withPersonalTeam()->create();

        $this->call([
            SuperAdminSeeder::class,
            MemberTypesSeeder::class,               // Member types (Individual, Group, etc)
            CompleteUgandaLocationsSeeder::class,  // All Uganda districts
            TesoRegionLocationsSeeder::class,       // Detailed Teso region data
            FeeTypesSeeder::class,                  // Fee types from old EBIMS system
        ]);
    }
}
