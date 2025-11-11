<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MemberTypesSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Skip if member types already exist (from SQL import)
        if (DB::table('member_types')->count() > 0) {
            $this->command->info('⚠️  Member types already exist. Skipping seeder.');
            return;
        }

        $memberTypes = [
            [
                'id' => 1,
                'name' => 'Individual',
                'description' => 'Individual member account for personal banking and loans',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'name' => 'Group',
                'description' => 'Group member account for group-based lending and savings',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'name' => 'Corporate',
                'description' => 'Corporate member account for business entities and organizations',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 4,
                'name' => 'Institution',
                'description' => 'Institutional member account for schools, hospitals, and other institutions',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('member_types')->insert($memberTypes);
    }
}
