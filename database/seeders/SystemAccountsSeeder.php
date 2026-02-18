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
        $this->command->info('🔄 Seeding Complete Chart of Accounts...');

        $accounts = [
            // ==================== ASSETS ====================
            // Cash and Cash Equivalents
            ['code' => '10000', 'sub_code' => null, 'name' => 'Cash and Cash Equivalents', 'category' => 'Asset', 'accountType' => 'Bank', 'accountSubType' => 'Cash and cash equivalents', 'parent_account' => 0, 'is_cash_bank' => true, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '10000', 'sub_code' => '10020', 'name' => 'ABSA Bank (UGX) Checking', 'category' => 'Asset', 'accountType' => 'Bank', 'accountSubType' => 'Current', 'parent_account' => 1, 'is_cash_bank' => true, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '10000', 'sub_code' => '10021', 'name' => 'ABSA Bank (UGX) Savings', 'category' => 'Asset', 'accountType' => 'Bank', 'accountSubType' => 'Savings', 'parent_account' => 1, 'is_cash_bank' => true, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '10000', 'sub_code' => '10030', 'name' => 'Stanbic Bank (UGX) Checking', 'category' => 'Asset', 'accountType' => 'Bank', 'accountSubType' => 'Current', 'parent_account' => 1, 'is_cash_bank' => true, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '10000', 'sub_code' => '10031', 'name' => 'Stanbic Bank (UGX) Agency Banking', 'category' => 'Asset', 'accountType' => 'Bank', 'accountSubType' => 'Current', 'parent_account' => 1, 'is_cash_bank' => true, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '10000', 'sub_code' => '10040', 'name' => 'Mobile Wallet – 0761426069', 'category' => 'Asset', 'accountType' => 'Bank', 'accountSubType' => 'Cash and cash equivalents', 'parent_account' => 1, 'is_cash_bank' => true, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '10000', 'sub_code' => '10041', 'name' => 'Mobile Wallet – 0757317176', 'category' => 'Asset', 'accountType' => 'Bank', 'accountSubType' => 'Cash and cash equivalents', 'parent_account' => 1, 'is_cash_bank' => true, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],

            // ASSETS - Payment Clearing and Transit
            ['code' => '10100', 'sub_code' => null, 'name' => 'Payment Clearing and Transit', 'category' => 'Asset', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Clearing Accounts', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => true, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '10100', 'sub_code' => '10110', 'name' => 'Cash-in-Transit – EPR (CEW) Clearing', 'category' => 'Asset', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Clearing Accounts', 'parent_account' => 8, 'is_cash_bank' => false, 'is_clearing' => true, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '10100', 'sub_code' => '10120', 'name' => 'Mobile Money Pending Settlement', 'category' => 'Asset', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Clearing Accounts', 'parent_account' => 8, 'is_cash_bank' => false, 'is_clearing' => true, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '10100', 'sub_code' => '10130', 'name' => 'Bank Deposits in Transit', 'category' => 'Asset', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Clearing Accounts', 'parent_account' => 8, 'is_cash_bank' => false, 'is_clearing' => true, 'is_loan_receivable' => false, 'allow_manual_posting' => false],

            // ASSETS - Loan Receivables (Gross)
            ['code' => '11000', 'sub_code' => null, 'name' => 'Loan Receivables – Gross', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Loan Receivables', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => true, 'allow_manual_posting' => false],
            ['code' => '11000', 'sub_code' => '11010', 'name' => 'Loan Receivable – Tuition', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Loan Receivables', 'parent_account' => 12, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => true, 'allow_manual_posting' => false],
            ['code' => '11000', 'sub_code' => '11020', 'name' => 'Loan Receivable – Business', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Loan Receivables', 'parent_account' => 12, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => true, 'allow_manual_posting' => false],
            ['code' => '11000', 'sub_code' => '11030', 'name' => 'Loan Receivable – Salary', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Loan Receivables', 'parent_account' => 12, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => true, 'allow_manual_posting' => false],
            ['code' => '11000', 'sub_code' => '11040', 'name' => 'Loan Receivable – Housing Microfinance', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Loan Receivables', 'parent_account' => 12, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => true, 'allow_manual_posting' => false],

            // ASSETS - Loan Loss Provision (Contra Asset)
            ['code' => '11100', 'sub_code' => null, 'name' => 'Loan Loss Provision (Contra Asset)', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Contra Asset', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '11100', 'sub_code' => '11110', 'name' => 'Provision – Tuition Loans', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Contra Asset', 'parent_account' => 17, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '11100', 'sub_code' => '11120', 'name' => 'Provision – Business Loans', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Contra Asset', 'parent_account' => 17, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '11100', 'sub_code' => '11130', 'name' => 'Provision – Salary Loans', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Contra Asset', 'parent_account' => 17, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '11100', 'sub_code' => '11140', 'name' => 'Provision – Housing Microfinance', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Contra Asset', 'parent_account' => 17, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],

            // LIABILITIES - Parent
            ['code' => '20000', 'sub_code' => null, 'name' => 'Liabilities', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Liabilities', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],

            // LIABILITIES - Client Deposits
            ['code' => '21000', 'sub_code' => null, 'name' => 'Client Deposits', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Client Deposits', 'parent_account' => 22, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '21000', 'sub_code' => '21010', 'name' => 'Client Savings Deposits', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Client Deposits', 'parent_account' => 23, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '21000', 'sub_code' => '21020', 'name' => 'Compulsory Deposits', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Client Deposits', 'parent_account' => 23, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '21000', 'sub_code' => '21025', 'name' => 'Cash Collateral – Client Deposits (35% Security)', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Client Deposits', 'parent_account' => 23, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '21000', 'sub_code' => '21030', 'name' => 'Insurance Payable – Credit Insurance (1%)', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Insurance Payables', 'parent_account' => 23, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '21000', 'sub_code' => '21040', 'name' => 'Insurance Payable – Insurance Security Fund (3%)', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Insurance Payables', 'parent_account' => 23, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],

            // INCOME - Revenue Parent
            ['code' => '40000', 'sub_code' => null, 'name' => 'Revenue', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Revenue', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],

            // INCOME - Interest Income
            ['code' => '41000', 'sub_code' => null, 'name' => 'Interest Income', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Interest Income', 'parent_account' => 29, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '41000', 'sub_code' => '41010', 'name' => 'Interest Income – Tuition', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Interest Income', 'parent_account' => 30, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '41000', 'sub_code' => '41020', 'name' => 'Interest Income – Business', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Interest Income', 'parent_account' => 30, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '41000', 'sub_code' => '41030', 'name' => 'Interest Income – Salary', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Interest Income', 'parent_account' => 30, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '41000', 'sub_code' => '41040', 'name' => 'Interest Income – Housing Microfinance', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Interest Income', 'parent_account' => 30, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],

            // INCOME - Fees and Charges
            ['code' => '42000', 'sub_code' => null, 'name' => 'Fees and Charges', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Fees and Charges', 'parent_account' => 29, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '42000', 'sub_code' => '42010', 'name' => 'Loan Processing Fees', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Fees and Charges', 'parent_account' => 35, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '42000', 'sub_code' => '42020', 'name' => 'Late Payment Penalties', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Fees and Charges', 'parent_account' => 35, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '42000', 'sub_code' => '42030', 'name' => 'Transaction Charges', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Fees and Charges', 'parent_account' => 35, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '42000', 'sub_code' => '42040', 'name' => 'Client Onboarding Fees (First-time)', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Fees and Charges', 'parent_account' => 35, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '42000', 'sub_code' => '42050', 'name' => 'Loan Administration Fees (Pre-disbursement)', 'category' => 'Income', 'accountType' => 'Other Income', 'accountSubType' => 'Fees and Charges', 'parent_account' => 35, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],

            // EXPENSES - Operating Expenses Parent
            ['code' => '50000', 'sub_code' => null, 'name' => 'Operating Expenses', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Operating Expenses', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],

            // EXPENSES - Staff Costs
            ['code' => '51000', 'sub_code' => null, 'name' => 'Staff Costs', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Staff Costs', 'parent_account' => 41, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '51000', 'sub_code' => '51010', 'name' => 'Salaries and Wages', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Staff Costs', 'parent_account' => 42, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '51000', 'sub_code' => '51020', 'name' => 'Staff Benefits', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Staff Costs', 'parent_account' => 42, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '51000', 'sub_code' => '51030', 'name' => 'Staff Training', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Staff Costs', 'parent_account' => 42, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],

            // EXPENSES - Branch Operations
            ['code' => '52000', 'sub_code' => null, 'name' => 'Branch Operations', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Branch Operations', 'parent_account' => 41, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '52000', 'sub_code' => '52010', 'name' => 'Office Rent', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Branch Operations', 'parent_account' => 46, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '52000', 'sub_code' => '52020', 'name' => 'Utilities', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Branch Operations', 'parent_account' => 46, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '52000', 'sub_code' => '52030', 'name' => 'Office Supplies', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Branch Operations', 'parent_account' => 46, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],

            // EXPENSES - Technology and Systems
            ['code' => '53000', 'sub_code' => null, 'name' => 'Technology and Systems', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Technology', 'parent_account' => 41, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '53000', 'sub_code' => '53010', 'name' => 'Software Subscriptions', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Technology', 'parent_account' => 50, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '53000', 'sub_code' => '53020', 'name' => 'System Maintenance', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Technology', 'parent_account' => 50, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],

            // EXPENSES - Governance and Compliance
            ['code' => '54000', 'sub_code' => null, 'name' => 'Governance and Compliance', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Governance', 'parent_account' => 41, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '54000', 'sub_code' => '54010', 'name' => 'Audit Fees', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Governance', 'parent_account' => 53, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '54000', 'sub_code' => '54020', 'name' => 'Legal Fees', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Governance', 'parent_account' => 53, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '54000', 'sub_code' => '54030', 'name' => 'Regulatory Fees', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Governance', 'parent_account' => 53, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],

            // EXPENSES - Transport and Field Operations
            ['code' => '55000', 'sub_code' => null, 'name' => 'Transport and Field Operations', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Field Operations', 'parent_account' => 41, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '55000', 'sub_code' => '55010', 'name' => 'Fuel and Transport', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Field Operations', 'parent_account' => 57, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '55000', 'sub_code' => '55020', 'name' => 'Field Allowances', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Field Operations', 'parent_account' => 57, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],

            // EXPENSES - Loan Write-offs
            ['code' => '56000', 'sub_code' => null, 'name' => 'Loan Write-offs Expense', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Loan Losses', 'parent_account' => 41, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '56100', 'sub_code' => null, 'name' => 'Loan Loss Provision Expense (ECL)', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Loan Losses', 'parent_account' => 41, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            
            // ==================== MISSING CRITICAL ACCOUNTS ====================
            
            // ASSETS - Interest Receivable (Accrued)
            ['code' => '11200', 'sub_code' => null, 'name' => 'Interest Receivable (Accrued)', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Interest Receivable', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '11200', 'sub_code' => '11210', 'name' => 'Accrued Interest – Tuition', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Interest Receivable', 'parent_account' => 62, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '11200', 'sub_code' => '11220', 'name' => 'Accrued Interest – Business', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Interest Receivable', 'parent_account' => 62, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '11200', 'sub_code' => '11230', 'name' => 'Accrued Interest – Salary', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Interest Receivable', 'parent_account' => 62, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '11200', 'sub_code' => '11240', 'name' => 'Accrued Interest – Housing Microfinance', 'category' => 'Asset', 'accountType' => 'Accounts receivable (A/R)', 'accountSubType' => 'Interest Receivable', 'parent_account' => 62, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            
            // ASSETS - Suspense and Clearing
            ['code' => '10200', 'sub_code' => null, 'name' => 'Suspense and Clearing', 'category' => 'Asset', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Suspense Accounts', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => true, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '10200', 'sub_code' => '10210', 'name' => 'Unidentified Deposits', 'category' => 'Asset', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Suspense Accounts', 'parent_account' => 67, 'is_cash_bank' => false, 'is_clearing' => true, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '10200', 'sub_code' => '10220', 'name' => 'Overpayments Held', 'category' => 'Asset', 'accountType' => 'Other Current Assets', 'accountSubType' => 'Suspense Accounts', 'parent_account' => 67, 'is_cash_bank' => false, 'is_clearing' => true, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            
            // ASSETS - Fixed Assets
            ['code' => '15000', 'sub_code' => null, 'name' => 'Fixed Assets', 'category' => 'Asset', 'accountType' => 'Fixed assets', 'accountSubType' => 'Fixed Assets', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '15000', 'sub_code' => '15010', 'name' => 'Office Equipment', 'category' => 'Asset', 'accountType' => 'Fixed assets', 'accountSubType' => 'Fixed Assets', 'parent_account' => 70, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '15000', 'sub_code' => '15020', 'name' => 'Furniture and Fixtures', 'category' => 'Asset', 'accountType' => 'Fixed assets', 'accountSubType' => 'Fixed Assets', 'parent_account' => 70, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '15000', 'sub_code' => '15030', 'name' => 'Computer Hardware', 'category' => 'Asset', 'accountType' => 'Fixed assets', 'accountSubType' => 'Fixed Assets', 'parent_account' => 70, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '15100', 'sub_code' => null, 'name' => 'Accumulated Depreciation (Contra Asset)', 'category' => 'Asset', 'accountType' => 'Fixed assets', 'accountSubType' => 'Accumulated Depreciation', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            
            // LIABILITIES - Accrued Expenses
            ['code' => '22000', 'sub_code' => null, 'name' => 'Accrued Expenses', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Accrued Expenses', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '22000', 'sub_code' => '22010', 'name' => 'Salaries Payable', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Accrued Expenses', 'parent_account' => 75, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '22000', 'sub_code' => '22020', 'name' => 'Utilities Payable', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Accrued Expenses', 'parent_account' => 75, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '22000', 'sub_code' => '22030', 'name' => 'Rent Payable', 'category' => 'Liability', 'accountType' => 'Other Current Liabilities', 'accountSubType' => 'Accrued Expenses', 'parent_account' => 75, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            
            // ==================== EQUITY (CRITICAL - WAS MISSING) ====================
            ['code' => '30000', 'sub_code' => null, 'name' => 'Equity', 'category' => 'Equity', 'accountType' => 'Equity', 'accountSubType' => 'Equity', 'parent_account' => 0, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '31000', 'sub_code' => null, 'name' => 'Share Capital', 'category' => 'Equity', 'accountType' => 'Equity', 'accountSubType' => 'Share Capital', 'parent_account' => 79, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '31000', 'sub_code' => '31010', 'name' => 'Initial Capital Contribution', 'category' => 'Equity', 'accountType' => 'Equity', 'accountSubType' => 'Share Capital', 'parent_account' => 80, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
            ['code' => '32000', 'sub_code' => null, 'name' => 'Retained Earnings', 'category' => 'Equity', 'accountType' => 'Equity', 'accountSubType' => 'Retained Earnings', 'parent_account' => 79, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            ['code' => '33000', 'sub_code' => null, 'name' => 'Current Year Profit/Loss', 'category' => 'Equity', 'accountType' => 'Equity', 'accountSubType' => 'Current Year', 'parent_account' => 79, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => false],
            
            // EXPENSES - Bank Charges (was missing)
            ['code' => '52000', 'sub_code' => '52040', 'name' => 'Bank Charges and Fees', 'category' => 'Expense', 'accountType' => 'Operating Expenses', 'accountSubType' => 'Branch Operations', 'parent_account' => 46, 'is_cash_bank' => false, 'is_clearing' => false, 'is_loan_receivable' => false, 'allow_manual_posting' => true],
        ];

        DB::transaction(function() use ($accounts) {
            foreach ($accounts as $index => $account) {
                $accountId = $index + 1;
                
                SystemAccount::updateOrCreate(
                    ['Id' => $accountId],
                    [
                        'code' => $account['code'],
                        'sub_code' => $account['sub_code'],
                        'name' => $account['name'],
                        'category' => $account['category'],
                        'accountType' => $account['accountType'],
                        'accountSubType' => $account['accountSubType'],
                        'currency' => 'Ugx',
                        'description' => '',
                        'parent_account' => $account['parent_account'],
                        'running_balance' => 0,
                        'is_cash_bank' => $account['is_cash_bank'],
                        'is_clearing' => $account['is_clearing'],
                        'is_loan_receivable' => $account['is_loan_receivable'],
                        'allow_manual_posting' => $account['allow_manual_posting'],
                        'added_by' => 1,
                        'status' => 1
                    ]
                );
            }
        });

        $this->command->info('✅ Chart of Accounts seeded successfully with ' . count($accounts) . ' accounts.');
        $this->command->info('');
        $this->command->info('📊 Account Categories:');
        $this->command->info('   ✓ Assets (Cash, Loans, Receivables, Fixed Assets)');
        $this->command->info('   ✓ Liabilities (Client Deposits, Insurance, Accrued Expenses)');
        $this->command->info('   ✓ Equity (Share Capital, Retained Earnings, P/L)');
        $this->command->info('   ✓ Income (Interest, Fees, Charges)');
        $this->command->info('   ✓ Expenses (Operations, Staff, Technology, Compliance)');
        $this->command->info('');
        $this->command->info('🔒 System-controlled accounts (allow_manual_posting = FALSE):');
        $this->command->info('   - All Cash/Bank accounts');
        $this->command->info('   - All Loan Receivables');
        $this->command->info('   - Insurance Payables');
        $this->command->info('   - Interest Accrued');
        $this->command->info('   - Fixed Assets Parent & Depreciation');
        $this->command->info('   - Equity Parent & Retained Earnings');
    }
}
