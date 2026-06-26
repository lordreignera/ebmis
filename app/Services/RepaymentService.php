<?php

namespace App\Services;

use App\Models\Repayment;
use App\Models\LoanSchedule;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Services\MobileMoneyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepaymentService
{
    private MobileMoneyService $mobileMoneyService;
    private AccountingService $accountingService;
    
    public function __construct(MobileMoneyService $mobileMoneyService, AccountingService $accountingService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
        $this->accountingService = $accountingService;
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
            $paymentType = $paymentData['type'] ?? '2'; // 1=cash, 2=mobile money, 3=bank

            if ((int) $paymentType !== 2 && (!auth()->check() || !auth()->user()?->isSuperAdmin())) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Only the Super Administrator can confirm cash or bank repayments. Please use Mobile Money.'
                ];
            }
            
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
                // Cash or bank payment - already restricted to Super Administrator above.
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

            // Resolve borrower name for the collection request
            $loan = PersonalLoan::with('member')->find($repayment->loan_id)
                 ?? GroupLoan::with('member')->find($repayment->loan_id);
            $payerName = $loan?->member?->full_name ?? 'Loan Borrower';

            // Collect mobile money payment FROM the borrower (repayment = collection, not disbursement)
            $paymentResult = $this->mobileMoneyService->collectMoney($payerName, $phone, $amount, 'Loan repayment');

            // Extract transaction reference — Stanbic returns 'reference', Emuria returns 'transaction_reference'
            $transactionRef = $paymentResult['transaction_reference'] ?? $paymentResult['reference'] ?? '';

            if (!$paymentResult['success']) {
                return [
                    'success' => false,
                    'message' => $paymentResult['message'] ?? 'Mobile money collection failed',
                    'transaction_reference' => $transactionRef
                ];
            }

            if (!$transactionRef) {
                return [
                    'success' => false,
                    'message' => 'Mobile money collection was initiated without a transaction reference'
                ];
            }

            // Update repayment with transaction details and leave it pending.
            // Mobile money is completed only by callback/status confirmation.
            $repayment->update([
                'txn_id' => $transactionRef,
                'transaction_reference' => $transactionRef,
                'payment_status' => 'Pending',
                'pay_status' => 'PENDING',
                'pay_message' => $paymentResult['message'] ?? 'Mobile money collection initiated',
                'payment_phone' => $phone,
                'network' => $network
            ]);
            
            return [
                'success' => true,
                'message' => 'Mobile money collection initiated. Awaiting callback confirmation.',
                'transaction_reference' => $transactionRef
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
            
            // If schedule is NULL (old system loans), auto-find next unpaid schedule
            if (!$schedule && $repayment->loan_id) {
                Log::info("Schedule not found for repayment {$repaymentId}, attempting auto-find for loan {$repayment->loan_id}");
                
                $schedule = \App\Models\LoanSchedule::where('loan_id', $repayment->loan_id)
                    ->where('status', '!=', 1) // Not fully paid
                    ->orderBy('id', 'asc')
                    ->first();
                
                if ($schedule) {
                    // Link the repayment to the found schedule
                    $repayment->update(['schedule_id' => $schedule->id]);
                    Log::info("Auto-linked repayment {$repaymentId} to schedule {$schedule->id}");
                } else {
                    DB::rollBack();
                    return [
                        'success' => false,
                        'message' => 'No unpaid schedules found for this loan'
                    ];
                }
            }
            
            if (!$schedule) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Loan schedule not found'
                ];
            }
            
            $loanResult = $this->getLoanForAccounting((int) $schedule->loan_id);
            $balanceAsOf = null;
            if ((int) $repayment->type === 2 && $repayment->date_created) {
                $balanceAsOf = $repayment->date_created instanceof Carbon
                    ? $repayment->date_created
                    : Carbon::parse($repayment->date_created);
            }
            $beforePayment = $this->getScheduleOutstandingComponents($schedule, $loanResult, $balanceAsOf);

            // Check if schedule is already fully paid by the official balance rule.
            if ($beforePayment['outstanding'] <= 1) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'This installment has already been fully paid'
                ];
            }

            if (abs((float) $repayment->amount - $beforePayment['outstanding']) > 1) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => sprintf(
                        'Payment must equal the exact schedule balance. Required UGX %s, received UGX %s.',
                        number_format($beforePayment['outstanding'], 0),
                        number_format((float) $repayment->amount, 0)
                    )
                ];
            }
            
            // Update repayment status
            $repayment->update([
                'status' => '1', // Approved
                'payment_status' => (int) $repayment->type === 2 ? 'Completed' : 'Confirmed',
                'pay_status' => $statusCode,
                'pay_message' => $statusMessage
            ]);
            
            // Exact schedule balance has been paid. Close this schedule and freeze late fees.
            $schedule->update([
                'paid' => $schedule->payment,
                'pending_count' => 0,
                'status' => 1,
                'date_cleared' => now(),
            ]);

            // Check if all schedules are paid - close the loan
            $this->checkAndCloseLoanIfComplete($schedule->loan_id);

            // Post repayment journal for service-based approvals.
            $loanForAccounting = $loanResult ?: $this->getLoanForAccounting((int) $schedule->loan_id);
            if ($loanForAccounting) {
                $journal = $this->accountingService->postRepaymentEntry($repayment->fresh(), $loanForAccounting);
                if (!$journal) {
                    Log::warning('Repayment approved but journal posting failed', [
                        'repayment_id' => $repaymentId,
                        'loan_id' => $schedule->loan_id,
                    ]);
                }
            } else {
                Log::warning('Repayment approved but loan not found for journal posting', [
                    'repayment_id' => $repaymentId,
                    'loan_id' => $schedule->loan_id,
                ]);
            }
            
            DB::commit();
            
            // Calculate outstanding balance
            $outstandingBalance = $this->getScheduleOutstandingComponents($schedule->fresh(), $loanResult)['outstanding'];
            
            Log::info("Repayment approved", [
                'repayment_id' => $repaymentId,
                'amount' => $repayment->amount,
                'schedule_id' => $schedule->id,
                'outstanding_balance' => $outstandingBalance
            ]);
            
            return [
                'success' => true,
                'message' => 'Payment confirmed successfully',
                'outstanding_balance' => $outstandingBalance,
                'schedule_paid' => $schedule->status == 1
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
            Log::info('Processing payment callback', [
                'fields' => array_keys($callbackData),
            ]);
            
            // Process callback through mobile money service
            $callbackResult = $this->mobileMoneyService->processCallback($callbackData);
            
            if (!($callbackResult['valid'] ?? false)) {
                return [
                    'success' => false,
                    'message' => 'Invalid callback data: ' . $callbackResult['message']
                ];
            }
            
            $transactionRef = $callbackResult['transaction_reference'];
            $statusCode = (string) ($callbackResult['status_code'] ?? '');
            $statusDesc = $callbackResult['status_description'];
            $statusCodeUpper = strtoupper($statusCode);
            // Only exact status codes are trusted — text matching on description is unsafe.
            // e.g. "Transaction not successful" contains "successful" and would be a false positive.
            $isSuccessfulStatus = in_array($statusCodeUpper, ['00', '01']);
            
            // Try to find the repayment record first
            $repayment = Repayment::where(function($query) use ($transactionRef) {
                                $query->where('txn_id', $transactionRef)
                                      ->orWhere('transaction_reference', $transactionRef);
                            })
                                ->where('status', '0')
                                ->first();

            // Fallback: if callback reference points to raw_payments.ref, resolve to trans_id first.
            if (!$repayment) {
                $rawPayment = DB::table('raw_payments')
                    ->where(function ($query) use ($transactionRef) {
                        $query->where('trans_id', $transactionRef)
                            ->orWhere('ref', $transactionRef);
                    })
                    ->where('type', 'repayment')
                    ->orderByDesc('id')
                    ->first();

                if ($rawPayment && !empty($rawPayment->trans_id)) {
                    $repayment = Repayment::where(function($query) use ($rawPayment) {
                                        $query->where('txn_id', $rawPayment->trans_id)
                                              ->orWhere('transaction_reference', $rawPayment->trans_id);
                                    })
                                        ->where('status', '0')
                                        ->first();
                }
            }
            
            if ($repayment) {
                // Handle repayment
                if ($isSuccessfulStatus) {
                    $result = $this->approveRepayment($repayment->id, $statusCodeUpper, $statusDesc);
                    
                    return [
                        'success' => $result['success'],
                        'message' => $result['message'],
                        'repayment_id' => $repayment->id
                    ];
                } else {
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
            }
            
            // Try to find a fee record
            $fee = \App\Models\Fee::where('pay_ref', $transactionRef)
                                ->where('status', 0)
                                ->first();
            
            if ($fee) {
                // Handle fee payment
                if ($isSuccessfulStatus) {
                    $fee->update([
                        'status' => 1,
                        'payment_status' => 'Completed',
                        'payment_description' => 'Payment confirmed via callback',
                        'payment_raw' => json_encode($callbackResult)
                    ]);
                    
                    Log::info("Fee payment confirmed via callback", ['fee_id' => $fee->id]);
                    
                    // Post to General Ledger
                    try {
                        $accountingService = new \App\Services\AccountingService();
                        $journal = $accountingService->postFeeCollectionEntry($fee, $fee->fees_type_id);
                        if ($journal) {
                            Log::info('Fee GL entry posted via callback', [
                                'fee_id' => $fee->id,
                                'journal_number' => $journal->journal_number
                            ]);
                        }
                    } catch (\Exception $glError) {
                        Log::error('Fee GL posting failed via callback', [
                            'fee_id' => $fee->id,
                            'error' => $glError->getMessage()
                        ]);
                        // Continue — do not fail the callback acknowledgement if GL posting fails
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'Fee payment confirmed successfully',
                        'fee_id' => $fee->id
                    ];
                } else {
                    $fee->update([
                        'status' => 2,
                        'payment_status' => 'Failed',
                        'payment_description' => $statusDesc,
                        'payment_raw' => json_encode($callbackResult)
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => "Fee payment failed: {$statusDesc}",
                        'fee_id' => $fee->id
                    ];
                }
            }
            
            // Try to find a cash security record
            $cashSecurity = \App\Models\CashSecurity::where(function ($query) use ($transactionRef) {
                                    $query->where('pay_ref', $transactionRef)
                                        ->orWhere('transaction_reference', $transactionRef);
                                })
                                ->where('status', 0)
                                ->first();
            
            if ($cashSecurity) {
                // Handle cash security payment
                if ($isSuccessfulStatus) {
                    $cashSecurity->update([
                        'status' => 1,
                        'payment_status' => 'Completed',
                        'payment_description' => 'Payment confirmed via callback',
                        'payment_raw' => json_encode($callbackResult)
                    ]);
                    
                    Log::info("Cash security payment confirmed via callback", ['cash_security_id' => $cashSecurity->id]);

                    try {
                        $journal = $this->accountingService->postCashSecurityEntry($cashSecurity);

                        if ($journal) {
                            Log::info('Cash security GL entry posted via callback', [
                                'cash_security_id' => $cashSecurity->id,
                                'journal_number' => $journal->journal_number,
                            ]);
                        }
                    } catch (\Exception $glError) {
                        Log::error('Cash security GL posting failed via callback', [
                            'cash_security_id' => $cashSecurity->id,
                            'error' => $glError->getMessage(),
                        ]);
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'Cash security payment confirmed successfully',
                        'cash_security_id' => $cashSecurity->id
                    ];
                } else {
                    $cashSecurity->update([
                        'status' => 2,
                        'payment_status' => 'Failed',
                        'payment_description' => $statusDesc,
                        'payment_raw' => json_encode($callbackResult)
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => "Cash security payment failed: {$statusDesc}",
                        'cash_security_id' => $cashSecurity->id
                    ];
                }
            }
            
            // Try to find a saving record
            $saving = \App\Models\Saving::where('txn_id', $transactionRef)
                                ->where('status', 0)
                                ->first();
            
            if ($saving) {
                // Handle savings deposit payment
                if ($isSuccessfulStatus) {
                    $saving->update([
                        'status' => 1,
                        'pay_status' => 'COMPLETED',
                        'pay_message' => json_encode($callbackResult)
                    ]);
                    
                    Log::info("Savings deposit confirmed via callback", ['saving_id' => $saving->id]);
                    
                    return [
                        'success' => true,
                        'message' => 'Savings deposit confirmed successfully',
                        'saving_id' => $saving->id
                    ];
                } else {
                    $saving->update([
                        'status' => 2,
                        'pay_status' => 'FAILED',
                        'pay_message' => json_encode($callbackResult)
                    ]);
                    
                    return [
                        'success' => false,
                        'message' => "Savings deposit failed: {$statusDesc}",
                        'saving_id' => $saving->id
                    ];
                }
            }
            
            // No matching record found
            Log::warning("No matching payment record found for transaction: {$transactionRef}");
            return [
                'success' => false,
                'message' => 'Payment record not found for transaction'
            ];
            
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
            $memberName = 'Unknown';
            if ($repayment->personalLoan && $repayment->personalLoan->member) {
                $memberName = $repayment->personalLoan->member->full_name;
            }
            
            return [
                'id' => $repayment->id,
                'amount' => $repayment->amount,
                'type' => $repayment->type,
                'date_created' => $repayment->date_created,
                'pay_status' => $repayment->pay_status,
                'txn_id' => $repayment->txn_id,
                'member_name' => $memberName
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
     * Sum payments that are valid for balance calculations.
     */
    public function getValidPaidForSchedule(int $scheduleId): float
    {
        return (float) Repayment::where('schedule_id', $scheduleId)
            ->where('amount', '>', 0)
            ->whereNotIn('status', [-1, 2])
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhere('payment_status', 'Completed');
            })
            ->sum('amount');
    }

    /**
     * Official schedule balance rule:
     * principal + interest + net late fees - valid payments.
     */
    public function getScheduleOutstandingComponents(LoanSchedule $schedule, $loan = null, ?Carbon $asOfDate = null): array
    {
        $loan = $loan ?: $this->getLoanForAccounting((int) $schedule->loan_id);
        if ($loan && method_exists($loan, 'loadMissing')) {
            $loan->loadMissing('product');
        }

        $principal = (float) ($schedule->principal ?? 0);
        $interest = (float) ($schedule->interest ?? 0);
        $lateFeeData = $loan
            ? $this->calculateLateFee($schedule, $loan, $asOfDate)
            : ['gross' => 0.0, 'net' => 0.0, 'waived' => 0.0, 'days_overdue' => 0, 'periods_overdue' => 0];
        $validPaid = $this->getValidPaidForSchedule((int) $schedule->id);
        $totalDue = $principal + $interest + (float) $lateFeeData['net'];

        return [
            'principal' => $principal,
            'interest' => $interest,
            'late_fee_gross' => (float) $lateFeeData['gross'],
            'late_fee_waived' => (float) $lateFeeData['waived'],
            'late_fee_net' => (float) $lateFeeData['net'],
            'valid_paid' => $validPaid,
            'total_due' => $totalDue,
            'outstanding' => max(0, $totalDue - $validPaid),
            'days_overdue' => (int) $lateFeeData['days_overdue'],
            'periods_overdue' => (int) $lateFeeData['periods_overdue'],
        ];
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
        
        $remainingBalance = $this->getScheduleOutstandingComponents($schedule)['outstanding'];

        if ($remainingBalance <= 1) {
            return [
                'valid' => false,
                'message' => 'This installment has already been fully paid'
            ];
        }

        if ($amount > $remainingBalance + 1) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Payment amount (UGX %s) exceeds the exact schedule balance (UGX %s)',
                    number_format($amount),
                    number_format($remainingBalance)
                )
            ];
        }

        if ($amount < $remainingBalance - 1) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Partial payments are not allowed. Please pay the exact schedule balance of UGX %s.',
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
     * NO WAIVERS - all schedules must be fully paid
     */
    public function checkAndCloseLoanIfComplete(int $loanId): bool
    {
        try {
            // Try PersonalLoan first, then GroupLoan
            $loan = PersonalLoan::with('product')->find($loanId);
            $loanType = 'personal';
            
            if (!$loan) {
                $loan = GroupLoan::with('product')->find($loanId);
                $loanType = 'group';
            }
            
            if (!$loan) {
                $loan = Loan::find($loanId);
                $loanType = 'other';
            }
            
            if (!$loan) {
                Log::warning("Loan {$loanId} not found in checkAndCloseLoanIfComplete");
                return false;
            }
            
            // Get all schedules with repayments
            $schedules = LoanSchedule::where('loan_id', $loanId)->get();
            
            if ($schedules->isEmpty()) {
                return false;
            }
            
            // Calculate total amount due (principal + interest + net late fees - valid payments)
            $totalDue = 0;
            $allSchedulesPaid = true;
            
            foreach ($schedules as $schedule) {
                $components = $this->getScheduleOutstandingComponents($schedule, $loan);
                $scheduleBalance = $components['outstanding'];
                $totalDue += $scheduleBalance;
                
                if ($scheduleBalance >= 1) {
                    $allSchedulesPaid = false;
                } else if ($scheduleBalance < 1 && $schedule->status != 1) {
                    // Mark schedule as paid
                    $schedule->update([
                        'pending_count' => 0,
                        'status' => 1,
                        'date_cleared' => now()
                    ]);
                }
            }
            
            // If total due is <= 0 (fully paid), close the loan
            if ($totalDue < 1 && $allSchedulesPaid) {
                if ($loan->status != 3) {
                    $loan->update([
                        'status' => 3, // Closed/Completed
                        'date_closed' => now()
                    ]);

                    Log::info("Loan automatically closed", [
                        'loan_id' => $loanId,
                        'loan_code' => $loan->code ?? 'N/A',
                        'loan_type' => $loanType,
                        'total_due' => $totalDue
                    ]);

                    return true;
                } else {
                    Log::info("Loan already closed", ['loan_id' => $loanId]);
                }
            } else {
                Log::info("Loan not ready to close", [
                    'loan_id' => $loanId,
                    'total_due' => $totalDue,
                    'all_schedules_paid' => $allSchedulesPaid,
                    'schedules_count' => $schedules->count()
                ]);
            }

            return false;

        } catch (\Exception $e) {
            Log::error("Failed to check/close loan {$loanId}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Parse payment dates that may be in DD-MM-YYYY format
     * MUST MATCH RepaymentController::parsePaymentDate() logic
     * 
     * @param string $dateString - Date in various formats
     * @return int Unix timestamp
     */
    private function parsePaymentDate($dateString)
    {
        // Parse DD-MM-YYYY format correctly
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $dateString, $matches)) {
            // DD-MM-YYYY format
            return mktime(0, 0, 0, $matches[2], $matches[1], $matches[3]);
        } else {
            // Fall back to strtotime for other formats
            return strtotime($dateString);
        }
    }
    
    /**
     * Calculate late fee for a schedule
     * CRITICAL: Late fees MULTIPLY while balance > 0, FREEZE when balance = 0
     * 
     * FORMULA: (P + I) × 6% × Periods Overdue
     * FREEZE LOGIC: Controlled by date_cleared field
     *   - date_cleared = NULL → balance > 0 → late fees continue multiplying (use TODAY)
     *   - date_cleared = [date] → balance = 0 → late fees frozen at that date
     * WAIVERS: Deducts from late_fees table where status = 2 (waived)
     * 
     * @param object $schedule - loan_schedules record
     * @param object $loan - loan record with product relationship
     * @return array ['gross' => total, 'net' => after_waivers, 'waived' => amount, 'days_overdue' => days, 'periods_overdue' => periods]
     */
    public function calculateLateFee($schedule, $loan, ?Carbon $asOfDate = null): array
    {
        // CRITICAL: Check date_cleared to determine if we freeze or continue multiplying
        if ($schedule->date_cleared) {
            // Balance = 0 at this date → FREEZE late fees at this point
            $now = strtotime($schedule->date_cleared);
        } elseif ($asOfDate) {
            // Validate pending mobile money against the balance shown when
            // the collection request was initiated.
            $now = $asOfDate->copy()->startOfDay()->timestamp;
        } else {
            // Balance > 0 → late fees continue MULTIPLYING using TODAY
            $now = time();
        }
        
        $your_date = $this->parsePaymentDate($schedule->payment_date);
        $d = max(0, floor(($now - $your_date) / 86400));
        
        $dd = 0;
        if ($d > 0) {
            $period_type = $loan->product ? $loan->product->period_type : '1';
            $dd = $period_type == '1' ? ceil($d / 7) : ($period_type == '2' ? ceil($d / 30) : $d);
        }
        
        $intrestamtpayable = $schedule->interest;
        $lateFeeOriginal = (($schedule->principal + $intrestamtpayable) * 0.06) * $dd;
        
        // Check for waivers in late_fees table (status = 2 means waived)
        $totalWaivedAmount = DB::table('late_fees')
            ->where('schedule_id', $schedule->id)
            ->where('status', 2) // Status 2 = waived
            ->sum('amount');
        
        $netLateFee = max(0, $lateFeeOriginal - $totalWaivedAmount);
        
        return [
            'gross' => $lateFeeOriginal,
            'net' => $netLateFee,
            'waived' => $totalWaivedAmount,
            'days_overdue' => $d,
            'periods_overdue' => $dd
        ];
    }
    
    /**
     * Calculate net late fee for a schedule (backward compatibility wrapper)
     * @deprecated Use calculateLateFee() for full data
     */
    private function calculateNetLateFee($schedule, $loan): float
    {
        $result = $this->calculateLateFee($schedule, $loan);
        return $result['net'];
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

        if ((int) $repayment->type === 2) {
            return [
                'success' => false,
                'message' => 'Mobile money repayments can only be completed by callback or status confirmation'
            ];
        }

        if (!auth()->check() || !auth()->user()?->isSuperAdmin()) {
            return [
                'success' => false,
                'message' => 'Only the Super Administrator can manually verify cash or bank repayments'
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

    /**
     * Resolve loan model with required relations for accounting posting.
     */
    private function getLoanForAccounting(int $loanId)
    {
        $loan = PersonalLoan::with(['product', 'member', 'branch'])->find($loanId);
        if ($loan) {
            return $loan;
        }

        return GroupLoan::with(['product', 'group', 'branch'])->find($loanId);
    }
}
