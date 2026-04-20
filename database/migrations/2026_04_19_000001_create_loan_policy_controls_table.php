<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_policy_controls', function (Blueprint $table) {
            $table->id();
            $table->string('key', 20)->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->decimal('value', 12, 4);
            // percent = stored as 0.70 displayed as 70%, score = integer 0-100,
            // multiplier = e.g. 1.20x, integer = whole number
            $table->enum('format', ['percent', 'multiplier', 'score', 'integer'])->default('integer');
            $table->timestamps();
        });

        // Seed default policy control values
        $now = now();
        DB::table('loan_policy_controls')->insert([
            [
                'key'         => 'HF',
                'label'       => 'Haircut Factor',
                'description' => 'Percentage of collateral value used in FSV calculations.',
                'value'       => 0.7000,
                'format'      => 'percent',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'MIN_DSCR',
                'label'       => 'Minimum DSCR',
                'description' => 'Minimum Debt Service Coverage Ratio required for approval.',
                'value'       => 1.2000,
                'format'      => 'multiplier',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'ABS_DSCR',
                'label'       => 'Absolute Decline DSCR Threshold',
                'description' => 'DSCR below this value results in automatic decline.',
                'value'       => 0.8000,
                'format'      => 'multiplier',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'STRESS',
                'label'       => 'Stress Factor',
                'description' => 'Income stress reduction applied in cash flow sensitivity analysis.',
                'value'       => 0.2000,
                'format'      => 'percent',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'APP_TH',
                'label'       => 'Approval Score Threshold',
                'description' => 'SDL score at or above this value qualifies for full approval.',
                'value'       => 75.0000,
                'format'      => 'score',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'ARA_TH',
                'label'       => 'Reduced Amount Score Threshold',
                'description' => 'SDL score at or above this value qualifies for a reduced loan amount.',
                'value'       => 65.0000,
                'format'      => 'score',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'CON_TH',
                'label'       => 'Conditional Score Threshold',
                'description' => 'SDL score at or above this value may proceed under conditions.',
                'value'       => 60.0000,
                'format'      => 'score',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'FRAUD_TH',
                'label'       => 'Fraud Flag Threshold',
                'description' => 'Number of fraud flags at or above this count triggers automatic decline.',
                'value'       => 3.0000,
                'format'      => 'integer',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'COL_MULT',
                'label'       => 'Collateral Coverage Multiple',
                'description' => 'Minimum ratio of collateral FSV to loan amount required.',
                'value'       => 2.0000,
                'format'      => 'multiplier',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'MIN_IVI',
                'label'       => 'Minimum IVI Score',
                'description' => 'Minimum In-Person Verification Integrity score required; below this blocks approval.',
                'value'       => 80.0000,
                'format'      => 'score',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'MIN_VSS',
                'label'       => 'Minimum VSS Score',
                'description' => 'Minimum Verification Strength Score required; below this blocks approval.',
                'value'       => 60.0000,
                'format'      => 'score',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'DVAR1',
                'label'       => 'Declaration Variance Warning Threshold',
                'description' => 'Income declaration variance above this percentage triggers a warning score penalty.',
                'value'       => 0.3000,
                'format'      => 'percent',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'DVAR2',
                'label'       => 'Declaration Variance Hard-Fail Threshold',
                'description' => 'Income declaration variance above this percentage results in a zero score.',
                'value'       => 0.5000,
                'format'      => 'percent',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'MPC',
                'label'       => 'Mandatory Policy Check Count',
                'description' => 'Total number of mandatory FVL policy checks used as the VCI denominator.',
                'value'       => 19.0000,
                'format'      => 'integer',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'MSR',
                'label'       => 'Merchant Service Rate',
                'description' => 'Mobile wallet repayment transaction fee rate charged per installment.',
                'value'       => 0.0160,
                'format'      => 'percent',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'AFR',
                'label'       => 'Admin Fee Rate',
                'description' => 'Loan administration fee charged as a percentage of the disbursed amount.',
                'value'       => 0.0500,
                'format'      => 'percent',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'IFR',
                'label'       => 'Insurance Fee Rate',
                'description' => 'Loan insurance fee rate charged as a percentage of the disbursed amount.',
                'value'       => 0.0000,
                'format'      => 'percent',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'RGF',
                'label'       => 'Registration Fee',
                'description' => 'Fixed one-time loan registration fee (UGX) deducted from disbursement.',
                'value'       => 50000.0000,
                'format'      => 'integer',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'FUC',
                'label'       => 'Follow-Up Cost per Delayed Installment',
                'description' => 'Fixed fee (UGX) charged per loan repayment follow-up visit on delayed accounts.',
                'value'       => 5000.0000,
                'format'      => 'integer',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'FVC',
                'label'       => 'Field Verification Cost',
                'description' => 'Fixed cost (UGX) of conducting a field verification visit for a loan application.',
                'value'       => 3000000.0000,
                'format'      => 'integer',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'LFR',
                'label'       => 'Late Fee Rate per Delayed Installment',
                'description' => 'Penalty rate charged as a percentage of the overdue installment amount.',
                'value'       => 0.0600,
                'format'      => 'percent',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'key'         => 'MIC',
                'label'       => 'Maximum Monthly Interest Cap',
                'description' => 'Regulatory cap on the maximum monthly interest rate that may be charged.',
                'value'       => 0.0280,
                'format'      => 'percent',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_policy_controls');
    }
};
