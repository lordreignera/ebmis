<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SavingsProduct;

class SavingsProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Skip if savings products already exist (from SQL import)
        if (SavingsProduct::count() > 0) {
            $this->command->info('⚠️  Savings products already exist. Skipping seeder.');
            return;
        }

        // Clear existing savings products
        SavingsProduct::truncate();

        // Create savings products based on old EBIMS data
        SavingsProduct::create([
            'id' => 1,
            'code' => 'BSV1738908165',
            'name' => 'Cash Security',
            'interest' => '0',
            'min_amt' => '0',
            'max_amt' => '0',
            'charge' => '0',
            'description' => 'Cash Security',
            'isactive' => 1,
            'account' => 51
        ]);

        // Add additional savings products for current system
        SavingsProduct::create([
            'id' => 2,
            'code' => 'SAV001',
            'name' => 'Basic Savings',
            'interest' => '5',
            'min_amt' => '10000',
            'max_amt' => '1000000',
            'charge' => '1000',
            'description' => 'Basic savings account with 5% annual interest',
            'isactive' => 1,
            'account' => 52
        ]);

        SavingsProduct::create([
            'id' => 3,
            'code' => 'SAV002',
            'name' => 'Premium Savings',
            'interest' => '8',
            'min_amt' => '50000',
            'max_amt' => '5000000',
            'charge' => '2000',
            'description' => 'Premium savings account with 8% annual interest',
            'isactive' => 1,
            'account' => 53
        ]);

        SavingsProduct::create([
            'id' => 4,
            'code' => 'SAV003',
            'name' => 'Fixed Deposit',
            'interest' => '12',
            'min_amt' => '100000',
            'max_amt' => '10000000',
            'charge' => '5000',
            'description' => 'Fixed deposit account with 12% annual interest',
            'isactive' => 1,
            'account' => 54
        ]);

        echo "Savings products seeded successfully.\n";
    }
}
