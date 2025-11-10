<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Disbursement;
use App\Models\Saving;
use App\Models\SavingsWithdraw;
use App\Models\Product;
use App\Services\FeeManagementService;
use App\Services\LoanScheduleService;
use Illuminate\Support\Facades\DB;

class LoanApprovalService
{
    private FeeManagementService $feeService;
    private LoanScheduleService $scheduleService;
    
    public function __construct(
        FeeManagementService $feeService,
        LoanScheduleService $scheduleService
    ) {
        $this->feeService = $feeService;
        $this->scheduleService = $scheduleService;
    }
    
    /**
     * Approve a personal loan
     */
    public function approvePersonalLoan($loan, array $approvalData): array
    {
        try {
            DB::beginTransaction();
            
            // Extract loan data
            $loanData = $this->extractLoanData($loan);
            $principal = (float) $loanData['principal'];
            $memberId = $loanData['member_id'];
            
            // Check if loan is restructured
            $isRestructured = $loanData['restructured'] ?? false;
            
            // Validate savings requirement (except for restructured loans)
            if (!$isRestructured) {
                $savingsValidation = $this->validateSavingsRequirement($memberId, $principal, $loanData['product_type']);
                if (!$savingsValidation['valid']) {
                    DB::rollBack();
                    return ['status' => false, 'msg' => $savingsValidation['message']];
                }
            }
            
            // Calculate disbursement amount based on charge type
            $chargeType = $approvalData['charge_type'] ?? '1';
            $disbursementCalculation = $this->feeService->calculateDisbursementAmount($loan, $chargeType);
            
            // Create disbursement record
            $disbursement = Disbursement::create([
                'loan_id' => $loanData['id'],
                'loan_type' => '1', // Personal loan
                'amount' => $disbursementCalculation['disbursement_amount'],
                'comments' => $approvalData['comments'] ?? '',
                'added_by' => auth()->id() ?? 1,
                'datecreated' => now(),
                'status' => '0' // Pending disbursement
            ]);
            
            // Update loan status to approved
            $this->updateLoanStatus($loan, 'verified', '1');
            
            // Create fee records
            $this->feeService->createLoanFees($loan, $chargeType);
            
            // Generate loan schedule
            $this->scheduleService->generateAndSaveSchedule($loan);
            
            DB::commit();
            
            return [
                'status' => true,
                'msg' => 'Loan approved successfully',
                'disbursement_id' => $disbursement->id,
                'disbursement_amount' => $disbursementCalculation['disbursement_amount']
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Loan approval failed: ' . $e->getMessage());
            
            return [
                'status' => false,
                'msg' => 'Loan approval failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Approve a group loan
     */
    public function approveGroupLoan($loan, array $approvalData): array
    {
        try {
            DB::beginTransaction();
            
            $loanData = $this->extractLoanData($loan);
            $principal = (float) $loanData['principal'];
            
            // Calculate disbursement amount
            $chargeType = $approvalData['charge_type'] ?? '1';
            $disbursementCalculation = $this->feeService->calculateDisbursementAmount($loan, $chargeType);
            
            // Create group disbursement record
            $disbursement = DB::table('group_disbursement')->insert([
                'loan_id' => $loanData['id'],
                'amount' => $disbursementCalculation['disbursement_amount'],
                'comments' => $approvalData['comments'] ?? '',
                'added_by' => auth()->id() ?? 1,
                'datecreated' => now(),
                'status' => '0'
            ]);
            
            // Update loan status
            $this->updateLoanStatus($loan, 'verified', '1');
            
            // Create fee records
            $this->feeService->createLoanFees($loan, $chargeType);
            
            // Generate loan schedule
            $this->scheduleService->generateAndSaveSchedule($loan);
            
            DB::commit();
            
            return [
                'status' => true,
                'msg' => 'Group loan approved successfully',
                'disbursement_amount' => $disbursementCalculation['disbursement_amount']
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Group loan approval failed: ' . $e->getMessage());
            
            return [
                'status' => false,
                'msg' => 'Group loan approval failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reject a loan
     */
    public function rejectLoan($loan, string $reason): array
    {
        try {
            $this->updateLoanStatus($loan, 'verified', '2');
            $this->updateLoanStatus($loan, 'comments', $reason);
            
            return [
                'status' => true,
                'msg' => 'Loan rejected successfully'
            ];
            
        } catch (\Exception $e) {
            \Log::error('Loan rejection failed: ' . $e->getMessage());
            
            return [
                'status' => false,
                'msg' => 'Loan rejection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate savings requirement for loan approval
     */
    private function validateSavingsRequirement(int $memberId, float $principal, int $productType): array
    {
        try {
            // Get member's savings balance
            $totalSavings = Saving::where('member_id', $memberId)->sum('value');
            $totalWithdrawals = SavingsWithdraw::where('member_id', $memberId)->sum('amount');
            $netSavings = $totalSavings - $totalWithdrawals;
            
            // Get product cash security requirement
            $product = Product::find($productType);
            if (!$product) {
                return [
                    'valid' => false,
                    'message' => 'Invalid loan product'
                ];
            }
            
            $requiredSavings = $principal * ($product->cash_sceurity / 100);
            
            if ($netSavings < $requiredSavings) {
                return [
                    'valid' => false,
                    'message' => sprintf(
                        "Savings (%s) aren't %s%% of principal. Required: %s",
                        number_format($netSavings, 2),
                        $product->cash_sceurity,
                        number_format($requiredSavings, 2)
                    )
                ];
            }
            
            return [
                'valid' => true,
                'message' => 'Savings requirement met',
                'net_savings' => $netSavings,
                'required_savings' => $requiredSavings
            ];
            
        } catch (\Exception $e) {
            \Log::error('Savings validation failed: ' . $e->getMessage());
            
            return [
                'valid' => false,
                'message' => 'Failed to validate savings requirement'
            ];
        }
    }
    
    /**
     * Check loan eligibility before approval
     */
    public function checkLoanEligibility($loan): array
    {
        $loanData = $this->extractLoanData($loan);
        $checks = [];
        
        // Check 1: Loan status (should be pending)
        if ($loanData['verified'] != '0') {
            $checks['loan_status'] = [
                'passed' => false,
                'message' => 'Loan is not in pending status'
            ];
        } else {
            $checks['loan_status'] = [
                'passed' => true,
                'message' => 'Loan status is valid for approval'
            ];
        }
        
        // Check 2: Savings requirement (for personal loans)
        if (!empty($loanData['member_id'])) {
            $savingsCheck = $this->validateSavingsRequirement(
                $loanData['member_id'],
                $loanData['principal'],
                $loanData['product_type']
            );
            $checks['savings'] = [
                'passed' => $savingsCheck['valid'],
                'message' => $savingsCheck['message']
            ];
        }
        
        // Check 3: Mandatory fees
        $feeValidation = $this->feeService->validateMandatoryFees($loan);
        $checks['mandatory_fees'] = [
            'passed' => $feeValidation['valid'],
            'message' => $feeValidation['message']
        ];
        
        // Check 4: Product validity
        $product = Product::find($loanData['product_type']);
        $checks['product'] = [
            'passed' => $product ? true : false,
            'message' => $product ? 'Valid loan product' : 'Invalid loan product'
        ];
        
        $allPassed = collect($checks)->every(function ($check) {
            return $check['passed'];
        });
        
        return [
            'eligible' => $allPassed,
            'checks' => $checks,
            'summary' => $allPassed ? 'Loan is eligible for approval' : 'Loan has eligibility issues'
        ];
    }
    
    /**
     * Generate loan approval summary
     */
    public function generateApprovalSummary($loan): array
    {
        $loanData = $this->extractLoanData($loan);
        $eligibility = $this->checkLoanEligibility($loan);
        
        // Calculate disbursement amounts for both charge types
        $deductedCharges = $this->feeService->calculateDisbursementAmount($loan, '1');
        $upfrontCharges = $this->feeService->calculateDisbursementAmount($loan, '2');
        
        return [
            'loan_details' => $loanData,
            'eligibility' => $eligibility,
            'disbursement_options' => [
                'deducted_charges' => $deductedCharges,
                'upfront_charges' => $upfrontCharges
            ],
            'recommended_action' => $eligibility['eligible'] ? 'approve' : 'review_issues'
        ];
    }
    
    /**
     * Update loan status
     */
    private function updateLoanStatus($loan, string $field, $value): bool
    {
        try {
            if ($loan instanceof PersonalLoan) {
                $loan->update([$field => $value]);
            } elseif ($loan instanceof GroupLoan) {
                $loan->update([$field => $value]);
            } elseif ($loan instanceof Loan) {
                $loan->update([$field => $value]);
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update loan status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extract loan data from different loan models
     */
    private function extractLoanData($loan): array
    {
        if ($loan instanceof PersonalLoan) {
            return [
                'id' => $loan->id,
                'member_id' => $loan->member_id,
                'principal' => $loan->principal,
                'interest' => $loan->interest,
                'period' => $loan->period,
                'product_type' => $loan->product_type,
                'verified' => $loan->verified,
                'restructured' => $loan->restructured ?? false,
                'created_at' => $loan->datecreated ?? $loan->created_at
            ];
        } elseif ($loan instanceof GroupLoan) {
            return [
                'id' => $loan->id,
                'group_id' => $loan->group_id,
                'principal' => $loan->principal,
                'interest' => $loan->interest,
                'period' => $loan->period,
                'product_type' => $loan->product_type,
                'verified' => $loan->verified,
                'created_at' => $loan->datecreated ?? $loan->created_at
            ];
        } elseif ($loan instanceof Loan) {
            return [
                'id' => $loan->id,
                'member_id' => $loan->member_id,
                'group_id' => $loan->group_id,
                'principal' => $loan->principal,
                'interest' => $loan->interest,
                'period' => $loan->period,
                'product_type' => $loan->product_type,
                'verified' => $loan->verified,
                'created_at' => $loan->created_at
            ];
        } else {
            throw new \InvalidArgumentException('Invalid loan model provided');
        }
    }
}