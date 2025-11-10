<?php

namespace App\Services;

use App\Models\Repayment;
use App\Models\LoanSchedule;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Services\MobileMoneyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepaymentService
{
    private MobileMoneyService $mobileMoneyService;
    
    public function __construct(MobileMoneyService $mobileMoneyService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
    }
    
    /**
     * Process a loan repayment
     */
    public function processRepayment(array $paymentData): array
    {
        try {
            DB::beginTransaction();
            
            $scheduleId = $paymentData['schedule_id'];
            $amount = (float) $paymentData['amount'];
            $paymentType = $paymentData['type'] ?? '1'; // 1=cash, 2=mobile money, 3=bank
            
            // Get schedule details
            $schedule = LoanSchedule::with('loan')->find($scheduleId);
            if (!$schedule) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Loan schedule not found'
                ];
            }
            
            // Validate payment amount
            $validation = $this->validatePaymentAmount($schedule, $amount);
            if (!$validation['valid']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Create repayment record
            $repayment = Repayment::create([
                'type' => $paymentType,
                'loan_id' => $schedule->loan_id,
                'schedule_id' => $scheduleId,
                'amount' => $amount,
                'date_created' => now(),
                'added_by' => auth()->id() ?? 1,
                'status' => '0', // Pending
                'platform' => 'Web',
                'pay_status' => 'PENDING'
            ]);
            
            // Process based on payment type
            if ($paymentType == '2') {
                // Mobile money payment
                $mobileResult = $this->processMobileMoneyPayment($repayment, $paymentData);
                if (!$mobileResult['success']) {
                    DB::rollBack();
                    return $mobileResult;
                }
            } else {
                // Cash or bank payment - auto approve
                $approvalResult = $this->approveRepayment($repayment->id, 'SUCCESS', 'Manual payment verified');
                if (!$approvalResult['success']) {
                    DB::rollBack();
                    return $approvalResult;
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'repayment_id' => $repayment->id,
                'payment_type' => $paymentType
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Repayment processing failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process mobile money payment
     */
    private function processMobileMoneyPayment(Repayment $repayment, array $paymentData): array
    {
        try {
            $phone = $paymentData['phone'] ?? '';
            $amount = $repayment->amount;
            $network = $paymentData['network'] ?? null;
            
            // Validate phone number
            $phoneValidation = $this->mobileMoneyService->validatePhoneNumber($phone);
            if (!$phoneValidation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Invalid phone number: ' . $phoneValidation['message']
                ];
            }
            
            // Process mobile money payment
            $paymentResult = $this->mobileMoneyService->disburse($phone, $amount, $network);
            
            // Update repayment with transaction details
            $repayment->update([
                'txn_id' => $paymentResult['transaction_reference'] ?? '',
                'pay_status' => $paymentResult['status_code'] ?? 'ERROR'
            ]);
            
            // If payment was successful, approve it
            if ($paymentResult['success']) {
                $this->approveRepayment(
                    $repayment->id,
                    $paymentResult['status_code'],
                    $paymentResult['message']
                );
            }
            
            return [
                'success' => $paymentResult['success'],
                'message' => $paymentResult['message'],
                'transaction_reference' => $paymentResult['transaction_reference'] ?? null
            ];
            
        } catch (\Exception $e) {
            Log::error('Mobile money payment processing failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Mobile money payment failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Approve a repayment and update loan schedule
     */
    public function approveRepayment(int $repaymentId, string $statusCode, string $statusMessage): array
    {
        try {
            DB::beginTransaction();
            
            $repayment = Repayment::with('schedule')->find($repaymentId);
            if (!$repayment) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Repayment not found'
                ];
            }
            
            // Check if already approved
            if ($repayment->status == '1') {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'This payment has already been verified and approved'
                ];
            }
            
            $schedule = $repayment->schedule;
            if (!$schedule) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Loan schedule not found'
                ];
            }
            
            // Check if schedule is already fully paid
            if ($schedule->status == '1') {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'This installment has already been fully paid'
                ];
            }
            
            // Calculate new balance
            $currentBalance = $schedule->balance ?: $schedule->payment;
            $newBalance = $currentBalance - $repayment->amount;
            
            // Update repayment status
            $repayment->update([
                'status' => '1', // Approved
                'pay_status' => $statusCode,
                'pay_message' => $statusMessage
            ]);
            
            // Update schedule
            if ($newBalance <= 500) {
                // Schedule is fully paid or nearly paid (auto-waive small balances)
                $schedule->update([
                    'balance' => 0,
                    'status' => '1', // Paid
                    'date_cleared' => now()
                ]);
                
                if ($newBalance > 0 && $newBalance <= 500) {
                    Log::info("Auto-waived small balance of {$newBalance} UGX for schedule ID: {$schedule->id}");
                }
            } else {
                // Update balance (still has significant amount remaining)
                $schedule->update([
                    'balance' => $newBalance
                ]);
            }
            
            // Check if all schedules are paid - close the loan
            $this->checkAndCloseLoanIfComplete($schedule->loan_id);
            
            DB::commit();
            
            Log::info("Repayment approved", [
                'repayment_id' => $repaymentId,
                'amount' => $repayment->amount,
                'schedule_id' => $schedule->id,
                'new_balance' => $newBalance
            ]);
            
            return [
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'new_balance' => $newBalance,
                'schedule_paid' => $newBalance <= 500
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Repayment approval failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Payment approval failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Process payment callback from mobile money provider
     */
    public function processPaymentCallback(array $callbackData): array
    {
        try {
            Log::info('Processing payment callback', $callbackData);
            
            // Process callback through mobile money service
            $callbackResult = $this->mobileMoneyService->processCallback($callbackData);
            
            if (!$callbackResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Invalid callback data: ' . $callbackResult['message']
                ];
            }
            
            $transactionRef = $callbackResult['transaction_reference'];
            $statusCode = $callbackResult['status_code'];
            $statusDesc = $callbackResult['status_description'];
            
            // Find the repayment record
            $repayment = Repayment::where('txn_id', $transactionRef)
                                ->where('status', '0')
                                ->first();
            
            if (!$repayment) {
                Log::warning("Repayment not found for transaction: {$transactionRef}");
                return [
                    'success' => false,
                    'message' => 'Repayment not found for transaction'
                ];
            }
            
            // If payment was successful, approve it
            if (in_array($statusCode, ['00', '01'])) {
                $result = $this->approveRepayment($repayment->id, $statusCode, $statusDesc);
                
                return [
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'repayment_id' => $repayment->id
                ];
            } else {
                // Payment failed - update status
                $repayment->update([
                    'pay_status' => $statusCode,
                    'pay_message' => $statusDesc
                ]);
                
                return [
                    'success' => false,
                    'message' => "Payment failed: {$statusDesc}",
                    'repayment_id' => $repayment->id
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Payment callback processing failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Callback processing failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get repayment details for a schedule
     */
    public function getScheduleRepayments(int $scheduleId): array
    {
        $repayments = Repayment::where('schedule_id', $scheduleId)
                             ->where('status', '1') // Only approved repayments
                             ->with(['personalLoan.member'])
                             ->get();
        
        // Filter successful payments using payment status
        $successfulRepayments = $repayments->filter(function ($repayment) {
            return $this->isPaymentSuccessful($repayment->pay_status);
        });
        
        return $successfulRepayments->map(function ($repayment) {
            return [
                'id' => $repayment->id,
                'amount' => $repayment->amount,
                'type' => $repayment->type,
                'date_created' => $repayment->date_created,
                'pay_status' => $repayment->pay_status,
                'txn_id' => $repayment->txn_id,
                'member_name' => $repayment->personalLoan?->member?->full_name ?? 'Unknown'
            ];
        })->values()->toArray();
    }
    
    /**
     * Check if payment status indicates success
     */
    private function isPaymentSuccessful(?string $payStatus): bool
    {
        if (!$payStatus) return false;
        
        $successCodes = ['SUCCESS', '00', '01'];
        $status = strtoupper(trim($payStatus));
        
        return in_array($status, $successCodes);
    }
    
    /**
     * Validate payment amount
     */
    private function validatePaymentAmount(LoanSchedule $schedule, float $amount): array
    {
        if ($amount <= 0) {
            return [
                'valid' => false,
                'message' => 'Payment amount must be greater than zero'
            ];
        }
        
        $remainingBalance = $schedule->balance ?: $schedule->payment;
        
        if ($amount > $remainingBalance + 1000) { // Allow small overpayment
            return [
                'valid' => false,
                'message' => sprintf(
                    'Payment amount (UGX %s) exceeds remaining balance (UGX %s)',
                    number_format($amount),
                    number_format($remainingBalance)
                )
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Payment amount is valid'
        ];
    }
    
    /**
     * Check if all schedules are paid and close loan if complete
     */
    private function checkAndCloseLoanIfComplete(int $loanId): bool
    {
        try {
            $unpaidSchedules = LoanSchedule::where('loan_id', $loanId)
                                         ->where('status', '0')
                                         ->count();
            
            if ($unpaidSchedules == 0) {
                // All schedules are paid - close the loan
                $loan = PersonalLoan::find($loanId) ?? 
                       GroupLoan::find($loanId) ?? 
                       Loan::find($loanId);
                
                if ($loan) {
                    $loan->update([
                        'status' => '1', // Complete
                        'date_closed' => now()
                    ]);
                    
                    Log::info("Loan {$loanId} marked as complete - all schedules paid");
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to check/close loan {$loanId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Manual verification of repayment (for admin use)
     */
    public function verifyRepayment(int $repaymentId): array
    {
        $repayment = Repayment::find($repaymentId);
        
        if (!$repayment) {
            return [
                'success' => false,
                'message' => 'Repayment not found'
            ];
        }
        
        if ($repayment->status == '1') {
            return [
                'success' => false,
                'message' => 'Repayment already verified'
            ];
        }
        
        return $this->approveRepayment($repaymentId, 'SUCCESS', 'Manually verified by admin');
    }
    
    /**
     * Get loan payment summary
     */
    public function getLoanPaymentSummary(int $loanId): array
    {
        $schedules = LoanSchedule::where('loan_id', $loanId)->get();
        $repayments = Repayment::where('loan_id', $loanId)
                              ->where('status', '1')
                              ->get();
        
        $totalScheduled = $schedules->sum('payment');
        $totalPaid = $repayments->sum('amount');
        $totalBalance = $schedules->where('status', '0')->sum('balance');
        
        return [
            'loan_id' => $loanId,
            'total_scheduled' => $totalScheduled,
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
            'schedules_count' => $schedules->count(),
            'paid_schedules' => $schedules->where('status', '1')->count(),
            'pending_schedules' => $schedules->where('status', '0')->count(),
            'completion_percentage' => $totalScheduled > 0 ? ($totalPaid / $totalScheduled) * 100 : 0
        ];
    }
}