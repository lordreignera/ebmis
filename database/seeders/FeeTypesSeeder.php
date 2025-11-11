<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FeeType;
use Illuminate\Support\Facades\DB;

class FeeTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Skip if fee types already exist (from SQL import)
        if (FeeType::count() > 0) {
            $this->command->info('⚠️  Fee types already exist. Skipping seeder.');
            return;
        }

        // Clear existing fee types
        FeeType::truncate();

        $feeTypes = [
            [
                'id' => 1,
                'name' => 'Late fees',
                'account' => 38,
                'added_by' => 1,
                'isactive' => true,
                'required_disbursement' => 0,
                'created_at' => '2022-08-02 05:04:49',
                'updated_at' => '2022-08-02 05:04:49'
            ],
            [
                'id' => 2,
                'name' => 'Registration fees',
                'account' => 39,
                'added_by' => 1,
                'isactive' => true,
                'required_disbursement' => 1,
                'created_at' => '2022-08-02 05:05:08',
                'updated_at' => '2022-08-02 05:05:08'
            ],
            [
                'id' => 3,
                'name' => 'Admin fees',
                'account' => 37,
                'added_by' => 1,
                'isactive' => true,
                'required_disbursement' => 1,
                'created_at' => '2022-08-06 10:26:41',
                'updated_at' => '2022-08-06 10:26:41'
            ],
            [
                'id' => 4,
                'name' => 'Insurance fee',
                'account' => 45,
                'added_by' => 1,
                'isactive' => true,
                'required_disbursement' => 1,
                'created_at' => '2022-08-06 10:27:02',
                'updated_at' => '2022-08-06 10:27:02'
            ],
            [
                'id' => 5,
                'name' => 'License fees',
                'account' => 42,
                'added_by' => 1,
                'isactive' => true,
                'required_disbursement' => 0,
                'created_at' => '2022-08-13 04:30:40',
                'updated_at' => '2022-08-13 04:30:40'
            ],
            [
                'id' => 6,
                'name' => 'Affiliation certificate',
                'account' => 43,
                'added_by' => 1,
                'isactive' => true,
                'required_disbursement' => 0,
                'created_at' => '2022-08-17 00:41:15',
                'updated_at' => '2022-08-17 00:41:15'
            ],
            [
                'id' => 7,
                'name' => 'Individual affiliation fee',
                'account' => 44,
                'added_by' => 1,
                'isactive' => true,
                'required_disbursement' => 0,
                'created_at' => '2022-08-24 09:23:01',
                'updated_at' => '2022-08-24 09:23:01'
            ],
            [
                'id' => 8,
                'name' => 'Restructuring fee',
                'account' => 41,
                'added_by' => 1,
                'isactive' => true,
                'required_disbursement' => 0,
                'created_at' => '2025-10-27 02:09:46',
                'updated_at' => '2025-10-27 02:09:46'
            ]
        ];

        // Insert fee types with explicit IDs
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        foreach ($feeTypes as $feeType) {
            FeeType::create($feeType);
        }
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Fee types seeded successfully.');
    }
}
