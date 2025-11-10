<?php

namespace App\Services;

use App\Models\Disbursement;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Services\FeeManagementService;
use App\Services\LoanScheduleService;
use App\Services\MobileMoneyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DisbursementService
{
    private FeeManagementService $feeService;
    private LoanScheduleService $scheduleService;
    private MobileMoneyService $mobileMoneyService;
    
    public function __construct(
        FeeManagementService $feeService,
        LoanScheduleService $scheduleService,
        MobileMoneyService $mobileMoneyService
    ) {
        $this->feeService = $feeService;
        $this->scheduleService = $scheduleService;
        $this->mobileMoneyService = $mobileMoneyService;
    }
    
    /**
     * Process loan disbursement
     */
    public function processLoanDisbursement(int $disbursementId, array $disbursementData): array
    {
        try {
            DB::beginTransaction();
            
            // Get disbursement record
            $disbursement = Disbursement::find($disbursementId);
            if (!$disbursement) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Disbursement record not found'
                ];
            }
            
            // Check if already disbursed
            if ($disbursement->status == '1') {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Disbursement already processed'
                ];
            }
            
            // Get loan details
            $loan = $this->getLoanById($disbursement->loan_id, $disbursement->loan_type);
            if (!$loan) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Loan not found'
                ];
            }
            
            // Validate mandatory fees
            $feeValidation = $this->feeService->validateMandatoryFees($loan);
            if (!$feeValidation['valid']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $feeValidation['message']
                ];
            }
            
            // Process based on disbursement type
            $disbursementType = $disbursementData['type'] ?? '0'; // 0=cash, 1=mobile money, 2=bank
            $disbursementResult = null;
            
            if ($disbursementType == '1') {
                // Mobile money disbursement
                $disbursementResult = $this->processMobileMoneyDisbursement($loan, $disbursementData);
                if (!$disbursementResult['success']) {
                    DB::rollBack();
                    return $disbursementResult;
                }
            }
            
            // Update disbursement record
            $updateData = [
                'payment_type' => $disbursementType,
                'status' => '1',
                'account_number' => $disbursementData['account_number'] ?? '',
                'date_approved' => now(),
                'inv_id' => $disbursementData['inv_id'] ?? null
            ];
            
            if ($disbursementResult) {
                $updateData['txn_reference'] = $disbursementResult['transaction_reference'] ?? '';
            }
            
            $disbursement->update($updateData);
            
            // Update loan status to disbursed
            $loanData = $this->extractLoanData($loan);
            $this->updateLoanStatus($loan, [
                'status' => '1', // Disbursed/Active
                'date_closed' => now()
            ]);
            
            // Update schedule dates based on disbursement date
            $disbursementDate = isset($disbursementData['d_date']) ? 
                              Carbon::parse($disbursementData['d_date']) : 
                              now();
            
            $this->scheduleService->updateScheduleDatesAfterDisbursement($loan, $disbursementDate);
            
            DB::commit();
            
            Log::info("Loan disbursement processed successfully", [
                'disbursement_id' => $disbursementId,
                'loan_id' => $disbursement->loan_id,
                'amount' => $disbursement->amount,
                'type' => $disbursementType
            ]);
            
            return [
                'success' => true,
                'message' => 'Disbursement processed successfully',
                'disbursement_id' => $disbursementId,
                'transaction_reference' => $disbursementResult['transaction_reference'] ?? null
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Disbursement processing failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Disbursement processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process mobile money disbursement
     */
    private function processMobileMoneyDisbursement($loan, array $disbursementData): array
    {
        $phone = $disbursementData['account_number'] ?? '';
        $amount = $disbursementData['loan_amt'] ?? 0;
        $network = $disbursementData['medium'] ?? null;
        
        if (!$phone) {
            return [
                'success' => false,
                'message' => 'Phone number is required for mobile money disbursement'
            ];
        }
        
        if (!$amount || $amount <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid disbursement amount'
            ];
        }
        
        // Validate phone number
        $phoneValidation = $this->mobileMoneyService->validatePhoneNumber($phone);
        if (!$phoneValidation['valid']) {
            return [
                'success' => false,
                'message' => 'Invalid phone number: ' . $phoneValidation['message']
            ];
        }
        
        // Process mobile money disbursement
        $disbursementResult = $this->mobileMoneyService->disburse(
            $phoneValidation['formatted_phone'],
            $amount,
            $network
        );
        
        if (!$disbursementResult['success']) {
            return [
                'success' => false,
                'message' => 'Mobile money disbursement failed: ' . $disbursementResult['message']
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Mobile money disbursement successful',
            'transaction_reference' => $disbursementResult['transaction_reference'],
            'phone' => $phoneValidation['formatted_phone'],
            'amount' => $amount
        ];
    }
    
    /**
     * Get pending disbursements
     */
    public function getPendingDisbursements(): array
    {
        $personalLoans = Disbursement::where('status', '0')
                                   ->where('loan_type', '1')
                                   ->with(['personalLoan.member', 'personalLoan.product'])
                                   ->get();
        
        $groupLoans = DB::table('group_disbursement')
                       ->where('status', '0')
                       ->join('group_loans', 'group_disbursement.loan_id', '=', 'group_loans.id')
                       ->join('groups', 'group_loans.group_id', '=', 'groups.id')
                       ->select('group_disbursement.*', 'groups.name as group_name', 'group_loans.principal')
                       ->get();
        
        return [
            'personal_loans' => $personalLoans->toArray(),
            'group_loans' => $groupLoans->toArray()
        ];
    }
    
    /**
     * Get disbursement summary for a loan
     */
    public function getDisbursementSummary($loan): array
    {
        $loanData = $this->extractLoanData($loan);
        
        // Calculate fees and disbursement amounts
        $deductedCharges = $this->feeService->calculateDisbursementAmount($loan, '1');
        $upfrontCharges = $this->feeService->calculateDisbursementAmount($loan, '2');
        
        // Get fee validation
        $feeValidation = $this->feeService->validateMandatoryFees($loan);
        
        // Check if already has disbursement record
        $disbursement = Disbursement::where('loan_id', $loanData['id'])
                                  ->where('loan_type', $loanData['loan_type'] ?? '1')
                                  ->first();
        
        return [
            'loan_details' => $loanData,
            'fee_validation' => $feeValidation,
            'disbursement_options' => [
                'deducted_charges' => $deductedCharges,
                'upfront_charges' => $upfrontCharges
            ],
            'existing_disbursement' => $disbursement ? [
                'id' => $disbursement->id,
                'amount' => $disbursement->amount,
                'status' => $disbursement->status,
                'created_at' => $disbursement->datecreated
            ] : null,
            'ready_for_disbursement' => $feeValidation['valid'] && $disbursement && $disbursement->status == '0'
        ];
    }
    
    /**
     * Reject disbursement
     */
    public function rejectDisbursement(int $disbursementId, string $reason): array
    {
        try {
            DB::beginTransaction();
            
            $disbursement = Disbursement::find($disbursementId);
            if (!$disbursement) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Disbursement not found'
                ];
            }
            
            // Update disbursement status
            $disbursement->update([
                'status' => '2', // Rejected
                'reject_comments' => $reason
            ]);
            
            // Update loan verification status
            $loan = $this->getLoanById($disbursement->loan_id, $disbursement->loan_type);
            if ($loan) {
                $this->updateLoanStatus($loan, ['verified' => '3']); // Rejected disbursement
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Disbursement rejected successfully'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Disbursement rejection failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Disbursement rejection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get loan by ID and type
     */
    private function getLoanById(int $loanId, string $loanType = '1')
    {
        if ($loanType == '1') {
            return PersonalLoan::find($loanId);
        } elseif ($loanType == '2') {
            return GroupLoan::find($loanId);
        } else {
            return Loan::find($loanId);
        }
    }
    
    /**
     * Update loan status
     */
    private function updateLoanStatus($loan, array $updates): bool
    {
        try {
            $loan->update($updates);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update loan status: ' . $e->getMessage());
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
                'loan_type' => '1',
                'member_id' => $loan->member_id,
                'principal' => $loan->principal,
                'product_type' => $loan->product_type,
                'verified' => $loan->verified,
                'restructured' => $loan->restructured ?? false,
                'created_at' => $loan->datecreated ?? $loan->created_at
            ];
        } elseif ($loan instanceof GroupLoan) {
            return [
                'id' => $loan->id,
                'loan_type' => '2',
                'group_id' => $loan->group_id,
                'principal' => $loan->principal,
                'product_type' => $loan->product_type,
                'verified' => $loan->verified,
                'created_at' => $loan->datecreated ?? $loan->created_at
            ];
        } elseif ($loan instanceof Loan) {
            return [
                'id' => $loan->id,
                'loan_type' => '3',
                'member_id' => $loan->member_id,
                'group_id' => $loan->group_id,
                'principal' => $loan->principal,
                'product_type' => $loan->product_type,
                'verified' => $loan->verified,
                'created_at' => $loan->created_at
            ];
        } else {
            throw new \InvalidArgumentException('Invalid loan model provided');
        }
    }
    
    /**
     * Test mobile money connectivity
     */
    public function testMobileMoneyConnection(): array
    {
        return $this->mobileMoneyService->testConnection();
    }
    
    /**
     * Get disbursement statistics
     */
    public function getDisbursementStatistics(): array
    {
        $personalDisbursements = Disbursement::where('loan_type', '1')->get();
        $groupDisbursements = DB::table('group_disbursement')->get();
        
        return [
            'personal_loans' => [
                'total' => $personalDisbursements->count(),
                'pending' => $personalDisbursements->where('status', '0')->count(),
                'approved' => $personalDisbursements->where('status', '1')->count(),
                'rejected' => $personalDisbursements->where('status', '2')->count(),
                'total_amount' => $personalDisbursements->where('status', '1')->sum('amount')
            ],
            'group_loans' => [
                'total' => $groupDisbursements->count(),
                'pending' => $groupDisbursements->where('status', '0')->count(),
                'approved' => $groupDisbursements->where('status', '1')->count(),
                'rejected' => $groupDisbursements->where('status', '2')->count(),
                'total_amount' => $groupDisbursements->where('status', '1')->sum('amount')
            ]
        ];
    }
}