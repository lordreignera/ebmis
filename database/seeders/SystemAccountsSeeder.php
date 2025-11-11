<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemAccount;
use Illuminate\Support\Facades\DB;

class SystemAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Skip if system accounts already exist (from SQL import)
        if (SystemAccount::count() > 0) {
            $this->command->info('⚠️  System accounts already exist. Skipping seeder.');
            return;
        }

        // Clear existing accounts
        SystemAccount::truncate();

        $accounts = [
            ['Id' => 1, 'code' => '10000', 'name' => 'Cash and cash equivalents', 'accountType' => 'Bank', 'accountSubType' => 'Cash and cash equivalents', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 2, 'code' => '10020', 'name' => 'ABSA Bank (UGX) Checking', 'accountType' => 'Bank', 'accountSubType' => 'Current', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 1, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 3, 'code' => '10021', 'name' => 'ABSA Bank (UGX) Savings', 'accountType' => 'Bank', 'accountSubType' => 'Savings', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 1, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 4, 'code' => '10011', 'name' => 'Cash on hand -Akore Branch', 'accountType' => 'Bank', 'accountSubType' => 'Cash on Hand', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 1, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 5, 'code' => '10010', 'name' => 'Cash on hand -Kampala Branch', 'accountType' => 'Bank', 'accountSubType' => 'Cash on Hand', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 1, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 6, 'code' => '10041', 'name' => 'Mobile Wallet - 0757317176', 'accountType' => 'Bank', 'accountSubType' => 'Cash and cash equivalents', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 1, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 7, 'code' => '10040', 'name' => 'Mobile Wallet - 0761426069', 'accountType' => 'Bank', 'accountSubType' => 'Cash and cash equivalents', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 1, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 8, 'code' => '10030', 'name' => 'Stanbic Bank (UGX) Checking', 'accountType' => 'Bank', 'accountSubType' => 'Current', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 1, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 9, 'code' => '10100', 'name' => 'Cash and Cash Equivalents (USD)', 'accountType' => 'Bank', 'accountSubType' => 'Cash and cash equivalents', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 1, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 10, 'code' => '10031', 'name' => 'Stanbic Bank (UGX) Agency Banking', 'accountType' => 'Bank', 'accountSubType' => 'Current', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 1, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 11, 'code' => '11000', 'name' => 'Loans Receivables', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Accounts receivable (A/R)', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 12, 'code' => '11010', 'name' => 'Tuition', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Accounts receivable (A/R)', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 11, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 13, 'code' => '11020', 'name' => 'Business', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Accounts receivable (A/R)', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 11, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 14, 'code' => '11030', 'name' => 'Salary', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Accounts receivable (A/R)', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 11, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 15, 'code' => '11040', 'name' => 'Housing Microfinance', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Accounts receivable (A/R)', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 11, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 16, 'code' => '11050', 'name' => 'Livestock trading', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Accounts receivable (A/R)', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 11, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 17, 'code' => '18000', 'name' => 'Control Accounts - Repayments', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Other Current Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 18, 'code' => '14000', 'name' => 'Inventory', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Other Current Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 19, 'code' => '12000', 'name' => 'Other Current Assets - (UGX)', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Other Current Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 20, 'code' => '12100', 'name' => 'Employee Cash Advances', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Other Current Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 19, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 21, 'code' => '13000', 'name' => 'Prepaid Expenses (UGX)', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Other Current Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 22, 'code' => '13030', 'name' => 'Other Amortised Startup Costs', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Other Current Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 21, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 23, 'code' => '13010', 'name' => 'Prepaid Rent Ugx', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Other Current Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 21, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 24, 'code' => '13020', 'name' => 'Prepaid Software Expenses', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Other Current Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 21, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 25, 'code' => '16500', 'name' => 'Unearned Loan Interest Receivable', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Other Current Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 26, 'code' => '15000', 'name' => 'Property, plant and equipment', 'accountType' => 'Fixed Assets', 'accountSubType' => 'Fixed Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 27, 'code' => '15100', 'name' => 'Computers and electronics', 'accountType' => 'Fixed Assets', 'accountSubType' => 'Fixed Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 26, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 28, 'code' => '15120', 'name' => 'Accumulated depreciation on property, plant and equipment', 'accountType' => 'Fixed Assets', 'accountSubType' => 'Fixed Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 26, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 29, 'code' => '15110', 'name' => 'Original Cost - Computers and Electronics', 'accountType' => 'Fixed Assets', 'accountSubType' => 'Fixed Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 26, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 30, 'code' => '15200', 'name' => 'Furniture and Fixtures', 'accountType' => 'Fixed Assets', 'accountSubType' => 'Fixed Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 26, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 31, 'code' => '15210', 'name' => 'Original Cost -Furniture and Fixtures', 'accountType' => 'Fixed Assets', 'accountSubType' => 'Fixed Assets', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 26, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 32, 'code' => '21000', 'name' => 'Other Current Liabilities - (UGX)', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Other Current Liabilities', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 33, 'code' => '23000', 'name' => 'Other Current Liabilities - (UGX):Accruals (UGX)', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Other Current Liabilities', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 34, 'code' => '23015', 'name' => 'Collateral on Loans', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Other Current Liabilities', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 33, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 35, 'code' => '23040', 'name' => 'Insurance on Loans payable', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Other Current Liabilities', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 33, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 36, 'code' => '40200', 'name' => 'Other Operating income', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 0, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 37, 'code' => '40210', 'name' => 'Admin fees', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 36, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 38, 'code' => '40240', 'name' => 'Penalties on Late Payment', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 36, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 39, 'code' => '40220', 'name' => 'Registration fees', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 36, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 40, 'code' => '40250', 'name' => 'Transaction Charges', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 36, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 41, 'code' => '40240', 'name' => 'Loan restructuring fees', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 36, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 42, 'code' => '40230', 'name' => 'License fees', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 36, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 43, 'code' => '40260', 'name' => 'Affiliation certificate', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 36, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 44, 'code' => '40270', 'name' => 'Individual affiliation fee', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 36, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 45, 'code' => '40280', 'name' => 'Insurance fee', 'accountType' => 'Other Income', 'accountSubType' => 'Other operating income', 'currency' => 'Ugx', 'description' => '', 'parent_account' => 36, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
            ['Id' => 51, 'code' => '23030', 'name' => 'Cash Security', 'accountType' => 'Current Liability', 'accountSubType' => 'Loan Collateral', 'currency' => 'Ugx', 'description' => 'Cash Security', 'parent_account' => 33, 'running_balance' => 0, 'added_by' => 1, 'status' => 1],
        ];

        foreach ($accounts as $account) {
            SystemAccount::create($account);
        }

        $this->command->info('System accounts seeded successfully.');
    }
}
