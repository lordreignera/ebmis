<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\SystemAccount;
use Illuminate\Support\Facades\DB;

class SchoolLoanProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get the first system account, or use ID 1 as fallback
        $account = SystemAccount::first();
        $accountId = $account ? ($account->Id ?? $account->id ?? 1) : 1;

        // Define the products to create
        $products = [
            // School Loan Products (loan_type = 4)
            [
                'code' => 'BLN' . time(),
                'name' => 'School Loan Daily',
                'description' => 'Daily repayment plan for school loans',
                'loan_type' => 4,
                'type' => 1,
                'period_type' => 1, // Daily
                'account' => $accountId,
                'max_amt' => 10000000, // 10 million
                'interest' => 2.5,
                'cash_sceurity' => 0,
                'isactive' => 1,
                'added_by' => 1,
            ],
            [
                'code' => 'BLN' . (time() + 1),
                'name' => 'School Loan Weekly',
                'description' => 'Weekly repayment plan for school loans',
                'loan_type' => 4,
                'type' => 1,
                'period_type' => 2, // Weekly
                'account' => $accountId,
                'max_amt' => 10000000,
                'interest' => 2.5,
                'cash_sceurity' => 0,
                'isactive' => 1,
                'added_by' => 1,
            ],
            [
                'code' => 'BLN' . (time() + 2),
                'name' => 'School Loan Monthly',
                'description' => 'Monthly repayment plan for school loans',
                'loan_type' => 4,
                'type' => 1,
                'period_type' => 3, // Monthly
                'account' => $accountId,
                'max_amt' => 10000000,
                'interest' => 10.0,
                'cash_sceurity' => 0,
                'isactive' => 1,
                'added_by' => 1,
            ],

            // Student Loan Products (loan_type = 5)
            [
                'code' => 'BLN' . (time() + 3),
                'name' => 'Student Loan Daily',
                'description' => 'Daily repayment plan for student loans',
                'loan_type' => 5,
                'type' => 1,
                'period_type' => 1, // Daily
                'account' => $accountId,
                'max_amt' => 5000000, // 5 million
                'interest' => 2.0,
                'cash_sceurity' => 0,
                'isactive' => 1,
                'added_by' => 1,
            ],
            [
                'code' => 'BLN' . (time() + 4),
                'name' => 'Student Loan Weekly',
                'description' => 'Weekly repayment plan for student loans',
                'loan_type' => 5,
                'type' => 1,
                'period_type' => 2, // Weekly
                'account' => $accountId,
                'max_amt' => 5000000,
                'interest' => 2.0,
                'cash_sceurity' => 0,
                'isactive' => 1,
                'added_by' => 1,
            ],
            [
                'code' => 'BLN' . (time() + 5),
                'name' => 'Student Loan Monthly',
                'description' => 'Monthly repayment plan for student loans',
                'loan_type' => 5,
                'type' => 1,
                'period_type' => 3, // Monthly
                'account' => $accountId,
                'max_amt' => 5000000,
                'interest' => 8.0,
                'cash_sceurity' => 0,
                'isactive' => 1,
                'added_by' => 1,
            ],

            // Staff Loan Products (loan_type = 6)
            [
                'code' => 'BLN' . (time() + 6),
                'name' => 'Staff Loan Daily',
                'description' => 'Daily repayment plan for staff loans',
                'loan_type' => 6,
                'type' => 1,
                'period_type' => 1, // Daily
                'account' => $accountId,
                'max_amt' => 3000000, // 3 million
                'interest' => 1.5,
                'cash_sceurity' => 0,
                'isactive' => 1,
                'added_by' => 1,
            ],
            [
                'code' => 'BLN' . (time() + 7),
                'name' => 'Staff Loan Weekly',
                'description' => 'Weekly repayment plan for staff loans',
                'loan_type' => 6,
                'type' => 1,
                'period_type' => 2, // Weekly
                'account' => $accountId,
                'max_amt' => 3000000,
                'interest' => 1.5,
                'cash_sceurity' => 0,
                'isactive' => 1,
                'added_by' => 1,
            ],
            [
                'code' => 'BLN' . (time() + 8),
                'name' => 'Staff Loan Monthly',
                'description' => 'Monthly repayment plan for staff loans',
                'loan_type' => 6,
                'type' => 1,
                'period_type' => 3, // Monthly
                'account' => $accountId,
                'max_amt' => 3000000,
                'interest' => 6.0,
                'cash_sceurity' => 0,
                'isactive' => 1,
                'added_by' => 1,
            ],
        ];

        // Check and create each product
        foreach ($products as $productData) {
            // Check if product already exists (by name and loan_type and period_type)
            $exists = Product::where('name', $productData['name'])
                ->where('loan_type', $productData['loan_type'])
                ->where('period_type', $productData['period_type'])
                ->exists();

            if (!$exists) {
                Product::create($productData);
                $this->command->info("Created: {$productData['name']}");
            } else {
                $this->command->info("Skipped (already exists): {$productData['name']}");
            }
        }

        $this->command->info('School loan products seeder completed successfully!');
    }
}
