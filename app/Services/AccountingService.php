<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\SystemAccount;
use App\Models\Fund;
use App\Models\Disbursement;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    /**
     * Post journal entry for loan disbursement
     */
    public function postDisbursementEntry(Disbursement $disbursement)
    {
        try {
            // Get loan details
            $loan = $disbursement->loan_type == 1 
                ? PersonalLoan::with(['member', 'product', 'branch'])->find($disbursement->loan_id)
                : GroupLoan::with(['group', 'product', 'branch'])->find($disbursement->loan_id);

            if (!$loan) {
                throw new \Exception("Loan not found for disbursement {$disbursement->id}");
            }

            // MAP PRODUCT TO SUB-CODE (same logic as repayment)
            $productName = strtolower($loan->product->name ?? '');
            $loanReceivableSubCode = null;
            
            if (str_contains($productName, 'school') || str_contains($productName, 'student') || str_contains($productName, 'tuition')) {
                $loanReceivableSubCode = '11010'; // Tuition loans
            } elseif (str_contains($productName, 'staff') || str_contains($productName, 'salary') || str_contains($productName, 'employee')) {
                $loanReceivableSubCode = '11030'; // Salary loans
            } elseif (str_contains($productName, 'housing') || str_contains($productName, 'mortgage') || str_contains($productName, 'property')) {
                $loanReceivableSubCode = '11040'; // Housing loans
            } else {
                $loanReceivableSubCode = '11020'; // Business/Quick loans (default)
            }
            
            // Find Loan Receivable sub-account for this product (11000 series = Gross Receivables)
            $loanReceivableAccount = SystemAccount::where('code', '11000')
                ->where('sub_code', $loanReceivableSubCode)
                ->first();

            if (!$loanReceivableAccount) {
                throw new \Exception("Loan Receivable sub-account {$loanReceivableSubCode} not found for product: {$loan->product->name}");
            }

            // Find Cash/Bank account based on payment type
            $cashAccount = $this->getCashAccountByPaymentType($disbursement);

            // Get fund if linked
            $fundId = null;
            if ($disbursement->inv_id) {
                $investment = \DB::table('investment')->where('id', $disbursement->inv_id)->first();
                if ($investment) {
                    $fund = Fund::where('name', $investment->name)->first();
                    if ($fund) {
                        $fundId = $fund->id;
                    }
                }
            }

            // Prepare journal entry data
            $borrowerName = $disbursement->loan_type == 1 
                ? ($loan->member->fname . ' ' . $loan->member->lname)
                : ($loan->group->name ?? 'Group');

            $journalData = [
                'transaction_date' => date('Y-m-d'),
                'reference_type' => 'Disbursement',
                'reference_id' => $disbursement->id,
                'narrative' => "Loan disbursement to {$borrowerName} - {$loan->code}",
                'cost_center_id' => $loan->branch_id ?? null,
                'product_id' => $loan->product_id ?? null,
                'officer_id' => $loan->assigned_to ?? auth()->id(),
                'fund_id' => $fundId,
            ];

            $journalLines = [
                [
                    'account_id' => $loanReceivableAccount->Id,
                    'debit' => $disbursement->amount,
                    'credit' => 0,
                    'narrative' => "Loan principal disbursed",
                ],
                [
                    'account_id' => $cashAccount->Id,
                    'debit' => 0,
                    'credit' => $disbursement->amount,
                    'narrative' => "Cash/bank payment to borrower",
                ],
            ];

            // Post the journal entry
            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Disbursement GL entry posted', [
                'disbursement_id' => $disbursement->id,
                'journal_number' => $journal->journal_number,
                'amount' => $disbursement->amount,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post disbursement GL entry', [
                'disbursement_id' => $disbursement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't throw - allow disbursement to complete even if GL posting fails
            // This can be corrected later with manual journal entries
            return null;
        }
    }

    /**
     * Get appropriate cash/bank account based on payment type
     */
    private function getCashAccountByPaymentType(Disbursement $disbursement)
    {
        // payment_type: 1=mobile_money, 2=bank/cheque/cash
        // medium: 1=Airtel, 2=MTN (for mobile money)
        
        $paymentSource = strtolower($disbursement->payment_source ?? '');
        
        if ($disbursement->payment_type == 1) {
            // Mobile money - use mobile wallet accounts (10040, 10041)
            $mmAccount = SystemAccount::where('code', '10000')
                ->where('sub_code', '10040')
                ->first();
            if ($mmAccount) return $mmAccount;
        } elseif (str_contains($paymentSource, 'stanbic')) {
            // Stanbic Bank
            $stanbicAccount = SystemAccount::where('code', '10000')
                ->where('sub_code', '10030')
                ->first();
            if ($stanbicAccount) return $stanbicAccount;
        } elseif (str_contains($paymentSource, 'absa')) {
            // ABSA Bank
            $absaAccount = SystemAccount::where('code', '10000')
                ->where('sub_code', '10020')
                ->first();
            if ($absaAccount) return $absaAccount;
        }

        // Default to mobile money wallet (most common payment method)
        $mmAccount = SystemAccount::where('code', '10000')
            ->where('sub_code', '10040')
            ->first();
        
        if ($mmAccount) return $mmAccount;
        
        // Last resort: parent cash account
        $cashAccount = SystemAccount::where('code', '10000')
            ->whereNull('sub_code')
            ->first();
        
        if ($cashAccount) return $cashAccount;

        throw new \Exception("No cash/bank account found for disbursement");
    }

    /**
     * Post journal entry for loan repayment
     * 
     * IMPORTANT: Repayments only include:
     * 1. Principal (reduces Loan Receivable)
     * 2. Interest (Interest Income - 41000 series)
     * 3. Late Fees/Penalties (Late Payment Penalties - 42020) - ONLY if paid late
     * 
     * All other fees (admin, insurance, processing, etc.) are collected BEFORE disbursement
     * during the approval stage and should NOT appear in repayment entries.
     */
    public function postRepaymentEntry($repayment, $loan)
    {
        try {
            // Get product information for account matching
            $productName = strtolower($loan->product->name ?? '');
            
            // MAP PRODUCT TO SUB-CODE
            // Tuition: 11010 (Receivable), 41010 (Interest)
            // Business: 11020 (Receivable), 41020 (Interest)
            // Salary: 11030 (Receivable), 41030 (Interest)
            // Housing: 11040 (Receivable), 41040 (Interest)
            
            $loanReceivableSubCode = null;
            $interestIncomeSubCode = null;
            
            if (str_contains($productName, 'school') || str_contains($productName, 'student') || str_contains($productName, 'tuition')) {
                // School/Student/Tuition loans → Tuition accounts
                $loanReceivableSubCode = '11010';
                $interestIncomeSubCode = '41010';
            } elseif (str_contains($productName, 'staff') || str_contains($productName, 'salary') || str_contains($productName, 'employee')) {
                // Staff/Salary loans → Salary accounts
                $loanReceivableSubCode = '11030';
                $interestIncomeSubCode = '41030';
            } elseif (str_contains($productName, 'housing') || str_contains($productName, 'mortgage') || str_contains($productName, 'property')) {
                // Housing/Mortgage loans → Housing accounts
                $loanReceivableSubCode = '11040';
                $interestIncomeSubCode = '41040';
            } else {
                // Default: Business/Quick/General loans → Business accounts
                $loanReceivableSubCode = '11020';
                $interestIncomeSubCode = '41020';
            }
            
            // 1. Find LOAN RECEIVABLE account (Principal reduces this asset) - Use specific sub-account (11000 series)
            $loanReceivableAccount = SystemAccount::where('code', '11000')
                ->where('sub_code', $loanReceivableSubCode)
                ->first();
            
            if (!$loanReceivableAccount) {
                throw new \Exception("Loan Receivable sub-account {$loanReceivableSubCode} not found for product: {$loan->product->name}");
            }

            // 2. Find INTEREST INCOME account (Interest earned) - Use specific sub-account
            $interestIncomeAccount = SystemAccount::where('code', '41000')
                ->where('sub_code', $interestIncomeSubCode)
                ->first();
            
            if (!$interestIncomeAccount) {
                throw new \Exception("Interest Income sub-account {$interestIncomeSubCode} not found for product: {$loan->product->name}");
            }

            // 3. Find LATE FEE/PENALTY INCOME account (Only for late payments) - Use sub-account
            $lateFeeAccount = SystemAccount::where('code', '42000')
                ->where('sub_code', '42020')
                ->first();
            
            if (!$lateFeeAccount) {
                $lateFeeAccount = SystemAccount::where('code', '42000')
                    ->whereNotNull('sub_code')
                    ->where(function($q) {
                        $q->where('name', 'LIKE', '%late%')
                          ->orWhere('name', 'LIKE', '%penalty%');
                    })
                    ->first();
            }
            
            if (!$lateFeeAccount) {
                // Fallback to parent
                $lateFeeAccount = SystemAccount::where('code', '42000')
                    ->whereNull('sub_code')
                    ->first();
            }

            // 4. Find CASH/BANK account based on payment method
            // Map payment method/source to specific cash accounts:
            // - Stanbic → 10030 (Stanbic Checking) or 10031 (Stanbic Agency)
            // - ABSA → 10020 (ABSA Checking) or 10021 (ABSA Savings)
            // - Mobile Money → 10040 or 10041 (Mobile Wallets)
            // - Cash → 10000 parent (Cash on Hand)
            // - Member Deposit/Savings → 21010 (Client Savings Deposits)
            
            $cashAccount = null;
            $paymentType = $repayment->type ?? $repayment->payment_type ?? null; // type: 1=cash, 2=mobile, 3=bank
            $paymentMethod = strtolower($repayment->payment_method ?? '');
            $paymentSource = strtolower($repayment->source ?? '');
            
            // Check payment type field first (1=cash, 2=mobile, 3=bank)
            if ($paymentType == 1) {
                // Cash payment - use parent cash account (Cash on Hand)
                $cashAccount = SystemAccount::where('code', '10000')
                    ->whereNull('sub_code')
                    ->first();
            } elseif ($paymentType == 2 || str_contains($paymentMethod, 'mobile') || str_contains($paymentMethod, 'momo') || 
                      str_contains($paymentSource, 'mobile') || str_contains($paymentSource, 'momo')) {
                // Mobile Money wallets
                $cashAccount = SystemAccount::where('code', '10000')
                    ->where('sub_code', '10040')
                    ->first();
            } elseif ($paymentType == 3 || str_contains($paymentMethod, 'bank') || str_contains($paymentSource, 'bank')) {
                // Bank payment - check which bank
                if (str_contains($paymentMethod, 'stanbic') || str_contains($paymentSource, 'stanbic')) {
                    $cashAccount = SystemAccount::where('code', '10000')
                        ->where('sub_code', '10030')
                        ->first();
                } elseif (str_contains($paymentMethod, 'absa') || str_contains($paymentSource, 'absa')) {
                    $cashAccount = SystemAccount::where('code', '10000')
                        ->where('sub_code', '10020')
                        ->first();
                } else {
                    // Generic bank - use Stanbic as default
                    $cashAccount = SystemAccount::where('code', '10000')
                        ->where('sub_code', '10030')
                        ->first();
                }
            } elseif (str_contains($paymentMethod, 'deposit') || str_contains($paymentMethod, 'savings') || str_contains($paymentMethod, 'balance')) {
                // Pay from member savings/deposit - use client deposits liability account
                $cashAccount = SystemAccount::where('code', '21000')
                    ->where('sub_code', '21010')
                    ->first();
            }
            
            // Fallback: use Mobile Money wallet as default (most common payment method)
            if (!$cashAccount) {
                $cashAccount = SystemAccount::where('code', '10000')
                    ->where('sub_code', '10040')
                    ->first();
            }
            
            // Last resort: parent cash account (should rarely happen)
            if (!$cashAccount) {
                $cashAccount = SystemAccount::where('code', '10000')
                    ->whereNull('sub_code')
                    ->first();
            }

            if (!$loanReceivableAccount || !$cashAccount) {
                throw new \Exception("Required accounts not found (Loan Receivable or Cash)");
            }

            $borrowerName = $loan->member ? 
                ($loan->member->fname . ' ' . $loan->member->lname) :
                ($loan->group->name ?? 'Unknown');

            $journalData = [
                'transaction_date' => $repayment->date_paid ?? date('Y-m-d'),
                'reference_type' => 'Repayment',
                'reference_id' => $repayment->id,
                'narrative' => "Loan repayment from {$borrowerName} - {$loan->code}",
                'cost_center_id' => $loan->branch_id ?? null,
                'product_id' => $loan->product_id ?? null,
                'officer_id' => $loan->assigned_to ?? null,
            ];

            $journalLines = [];

            // Calculate principal and interest from repayment
            // NOTE: Repayments table may not have principal/interest columns split out
            // We need to get this from the schedule or calculate it
            $principal = $repayment->principal ?? 0;
            $interest = $repayment->interest ?? 0;
            $penalty = $repayment->penalty ?? 0;
            
            // If principal and interest are not populated, get from schedule
            if ($principal == 0 && $interest == 0 && $repayment->amount > 0) {
                $schedule = \App\Models\LoanSchedule::find($repayment->schedule_id);
                if ($schedule) {
                    // Calculate proportionally based on schedule
                    $schedulePrincipal = $schedule->principal ?? 0;
                    $scheduleInterest = $schedule->interest ?? 0;
                    $scheduleTotal = $schedulePrincipal + $scheduleInterest;
                    
                    if ($scheduleTotal > 0) {
                        // Proportion the payment between principal and interest
                        $principal = ($repayment->amount * $schedulePrincipal) / $scheduleTotal;
                        $interest = ($repayment->amount * $scheduleInterest) / $scheduleTotal;
                    } else {
                        // If no breakdown available, treat entire amount as principal
                        $principal = $repayment->amount;
                    }
                }
            }

            // Calculate total cash received (Principal + Interest + Late Fees ONLY)
            // NOTE: $repayment->fees should NOT exist in repayments as all fees are collected upfront
            $totalReceived = $principal + $interest + $penalty;

            // DR: Cash (total amount received from borrower)
            $journalLines[] = [
                'account_id' => $cashAccount->Id,
                'debit' => $totalReceived,
                'credit' => 0,
                'narrative' => "Cash received from borrower",
            ];

            // CR: Loan Receivable (principal portion reduces the asset)
            if ($principal > 0) {
                $journalLines[] = [
                    'account_id' => $loanReceivableAccount->Id,
                    'debit' => 0,
                    'credit' => $principal,
                    'narrative' => "Loan principal repaid - reduces receivable",
                ];
            }

            // CR: Interest Income (interest portion is revenue)
            if ($interest > 0 && $interestIncomeAccount) {
                $journalLines[] = [
                    'account_id' => $interestIncomeAccount->Id,
                    'debit' => 0,
                    'credit' => $interest,
                    'narrative' => "Interest income earned",
                ];
            }

            // CR: Late Fee/Penalty Income (penalties only if client paid late)
            if ($penalty > 0 && $lateFeeAccount) {
                $journalLines[] = [
                    'account_id' => $lateFeeAccount->Id,
                    'debit' => 0,
                    'credit' => $penalty,
                    'narrative' => "Late payment penalty - paid after due date",
                ];
            }

            // Post the journal entry
            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Repayment GL entry posted', [
                'repayment_id' => $repayment->id,
                'journal_number' => $journal->journal_number,
                'total_amount' => $totalReceived,
                'breakdown' => [
                    'principal' => $principal,
                    'interest' => $interest,
                    'penalty' => $penalty,
                ],
                'accounts_used' => [
                    'loan_receivable' => $loanReceivableAccount->code . ' - ' . $loanReceivableAccount->name,
                    'interest_income' => $interestIncomeAccount ? ($interestIncomeAccount->code . ' - ' . $interestIncomeAccount->name) : 'N/A',
                    'penalty_income' => $lateFeeAccount ? ($lateFeeAccount->code . ' - ' . $lateFeeAccount->name) : 'N/A',
                ]
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post repayment GL entry', [
                'repayment_id' => $repayment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Post journal entry for fee collection
     * 
     * Fees are collected at loan approval or member registration:
     * - Registration fees (42040 - Onboarding)
     * - Admin fees (42050 - Administration)
     * - Insurance fees (42000 series)
     * - Processing fees (42010)
     * - Late fees (42020) - if collected separately
     */
    public function postFeeCollectionEntry($fee, $feeType)
    {
        try {
            $feeName = strtolower($feeType->name);
            
            // Check if this is a security deposit (LIABILITY, not income)
            if (stripos($feeName, 'security') !== false) {
                // Use the dedicated cash security posting method
                return $this->postCashSecurityEntry($fee);
            }
            
            // Check if this is insurance (LIABILITY, not income)
            if (stripos($feeName, 'insurance') !== false) {
                return $this->postInsuranceFeeEntry($fee, $feeType);
            }
            
            // 1. Determine the correct income account based on fee type
            $incomeAccount = $this->getFeeIncomeAccount($feeType);
            
            if (!$incomeAccount) {
                throw new \Exception("Fee income account not found for fee type: {$feeType->name}");
            }

            // 2. Find CASH account based on payment type
            $cashAccount = $this->getCashAccountByPaymentMethod($fee->payment_type ?? 1, null, null);

            if (!$cashAccount) {
                throw new \Exception("Cash/Bank account not found");
            }

            // 3. Get member/loan details
            $member = \App\Models\Member::find($fee->member_id);
            $loan = null;
            if ($fee->loan_id) {
                $loan = \App\Models\PersonalLoan::with('product', 'branch')->find($fee->loan_id);
            }

            $memberName = $member ? ($member->fname . ' ' . $member->lname) : 'Unknown Member';

            // 4. Create journal entry
            $journalData = [
                'transaction_date' => $fee->created_at ?? date('Y-m-d'),
                'reference_type' => 'Fee Collection',
                'reference_id' => $fee->id,
                'narrative' => "Fee payment: {$feeType->name} from {$memberName}" . ($loan ? " - Loan {$loan->code}" : ''),
                'cost_center_id' => $loan->branch_id ?? null,
                'product_id' => $loan->product_id ?? null,
                'officer_id' => $fee->added_by ?? null,
            ];

            $journalLines = [];

            // DR: Cash (money received)
            $journalLines[] = [
                'account_id' => $cashAccount->Id,
                'debit' => $fee->amount,
                'credit' => 0,
                'narrative' => "Cash received - {$feeType->name}",
            ];

            // CR: Fee Income (revenue earned)
            $journalLines[] = [
                'account_id' => $incomeAccount->Id,
                'debit' => 0,
                'credit' => $fee->amount,
                'narrative' => "Fee income - {$feeType->name}",
            ];

            // Post the journal entry
            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Fee collection GL entry posted', [
                'fee_id' => $fee->id,
                'journal_number' => $journal->journal_number,
                'amount' => $fee->amount,
                'fee_type' => $feeType->name,
                'income_account' => $incomeAccount->code . ' - ' . $incomeAccount->name,
                'cash_account' => $cashAccount->code . ' - ' . $cashAccount->name,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post fee collection GL entry', [
                'fee_id' => $fee->id,
                'fee_type' => $feeType->name ?? 'Unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Determine the correct income account for a fee type
     */
    private function getFeeIncomeAccount($feeType)
    {
        $feeName = strtolower($feeType->name);

        // Registration fees → Onboarding Fees (42040)
        if (stripos($feeName, 'registration') !== false || stripos($feeName, 'onboarding') !== false) {
            return SystemAccount::where('code', '42000')
                ->where('sub_code', '42040')
                ->first();
        }

        // Processing fees → Loan Processing Fees (42010)
        if (stripos($feeName, 'processing') !== false) {
            return SystemAccount::where('code', '42000')
                ->where('sub_code', '42010')
                ->first();
        }

        // Late fees / Penalties → Late Payment Penalties (42020)
        if (stripos($feeName, 'late') !== false || stripos($feeName, 'penalty') !== false) {
            return SystemAccount::where('code', '42000')
                ->where('sub_code', '42020')
                ->first();
        }

        // Transaction charges → Transaction Charges (42030)
        if (stripos($feeName, 'transaction') !== false) {
            return SystemAccount::where('code', '42000')
                ->where('sub_code', '42030')
                ->first();
        }

        // Admin/Administration fees → Administration Fees (42050)
        if (stripos($feeName, 'admin') !== false) {
            return SystemAccount::where('code', '42000')
                ->where('sub_code', '42050')
                ->first();
        }

        // Insurance fees → Try to find specific insurance account or use generic fee income
        if (stripos($feeName, 'insurance') !== false) {
            $insuranceAccount = SystemAccount::where('category', 'Income')
                ->where(function($q) {
                    $q->where('name', 'LIKE', '%insurance%')
                      ->orWhere('name', 'LIKE', '%Insurance%');
                })
                ->first();
            
            if ($insuranceAccount) return $insuranceAccount;
        }

        // Security deposit → This might be a liability (refundable), not income
        if (stripos($feeName, 'security') !== false) {
            // Check for security deposit liability account
            $securityAccount = SystemAccount::where('category', 'Liability')
                ->where('name', 'LIKE', '%security%deposit%')
                ->first();
            
            if ($securityAccount) return $securityAccount;
        }

        // Default: Generic fee income (parent account 42000)
        return SystemAccount::where('code', '42000')
            ->whereNull('sub_code')
            ->first();
    }

    /**
     * Post journal entry for cash security deposit
     * 
     * Cash security is a LIABILITY (refundable to member when loan is settled)
     * DR: Cash/Bank (asset increases)
     * CR: Cash Security Liability (liability increases - we owe this back to member)
     */
    public function postCashSecurityEntry($cashSecurity)
    {
        try {
            // 1. Find CASH/BANK account based on payment type
            $cashAccount = $this->getCashAccountByPaymentMethod($cashSecurity->payment_type ?? 1, null, null);

            // 2. Find CASH SECURITY LIABILITY account (21025)
            $securityLiabilityAccount = SystemAccount::where('code', '21000')
                ->where('sub_code', '21025')
                ->first();
            
            if (!$securityLiabilityAccount) {
                // Fallback to parent liability account
                $securityLiabilityAccount = SystemAccount::where('code', '21000')
                    ->whereNull('sub_code')
                    ->first();
            }

            if (!$cashAccount || !$securityLiabilityAccount) {
                throw new \Exception("Required accounts not found (Cash or Cash Security Liability)");
            }

            // 3. Get member/loan details
            $member = \App\Models\Member::find($cashSecurity->member_id);
            $loan = null;
            if (isset($cashSecurity->loan_id) && $cashSecurity->loan_id) {
                $loan = \App\Models\PersonalLoan::with('product', 'branch')->find($cashSecurity->loan_id);
            }
            
            $memberName = $member ? ($member->fname . ' ' . $member->lname) : 'Unknown Member';

            // 4. Create journal entry
            $journalData = [
                'transaction_date' => $cashSecurity->created_at ?? ($cashSecurity->datecreated ?? date('Y-m-d')),
                'reference_type' => 'Cash Security',
                'reference_id' => $cashSecurity->id,
                'narrative' => "Cash security deposit from {$memberName}" . ($loan ? " - Loan {$loan->code}" : ''),
                'cost_center_id' => $loan->branch_id ?? null,
                'product_id' => $loan->product_id ?? null,
                'officer_id' => $cashSecurity->added_by ?? null,
            ];

            $journalLines = [];

            // DR: Cash (money received from member)
            $journalLines[] = [
                'account_id' => $cashAccount->Id,
                'debit' => $cashSecurity->amount,
                'credit' => 0,
                'narrative' => "Cash security deposit received",
            ];

            // CR: Cash Security Liability (we owe this money back to member when loan is settled)
            $journalLines[] = [
                'account_id' => $securityLiabilityAccount->Id,
                'debit' => 0,
                'credit' => $cashSecurity->amount,
                'narrative' => "Cash security liability - refundable to member",
            ];

            // Post the journal entry
            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Cash security GL entry posted', [
                'cash_security_id' => $cashSecurity->id,
                'journal_number' => $journal->journal_number,
                'amount' => $cashSecurity->amount,
                'member' => $memberName,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post cash security GL entry', [
                'cash_security_id' => $cashSecurity->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Post journal entry for insurance fee collection
     * 
     * Insurance fees are a LIABILITY (held for insurance purposes)
     * DR: Cash/Bank (asset increases)
     * CR: Insurance Payable (liability increases - insurance fund balance)
     */
    public function postInsuranceFeeEntry($fee, $feeType)
    {
        try {
            // 1. Find CASH/BANK account based on payment type
            $cashAccount = $this->getCashAccountByPaymentMethod($fee->payment_type ?? 1, null, null);

            // 2. Find INSURANCE PAYABLE LIABILITY account (21030 or 21040)
            // Use 21030 (Credit Insurance 1%) for smaller amounts, 21040 (Security Fund 3%) for larger
            $insuranceLiabilityAccount = SystemAccount::where('code', '21000')
                ->where('sub_code', '21030')
                ->first();
            
            if (!$insuranceLiabilityAccount) {
                // Fallback to parent liability account
                $insuranceLiabilityAccount = SystemAccount::where('code', '21000')
                    ->whereNull('sub_code')
                    ->first();
            }

            if (!$cashAccount || !$insuranceLiabilityAccount) {
                throw new \Exception("Required accounts not found (Cash or Insurance Payable)");
            }

            // 3. Get member/loan details
            $member = \App\Models\Member::find($fee->member_id);
            $loan = null;
            if ($fee->loan_id) {
                $loan = \App\Models\PersonalLoan::with('product', 'branch')->find($fee->loan_id);
            }
            
            $memberName = $member ? ($member->fname . ' ' . $member->lname) : 'Unknown Member';

            // 4. Create journal entry
            $journalData = [
                'transaction_date' => $fee->created_at ?? date('Y-m-d'),
                'reference_type' => 'Insurance Fee',
                'reference_id' => $fee->id,
                'narrative' => "Insurance fee from {$memberName}" . ($loan ? " - Loan {$loan->code}" : ''),
                'cost_center_id' => $loan->branch_id ?? null,
                'product_id' => $loan->product_id ?? null,
                'officer_id' => $fee->added_by ?? null,
            ];

            $journalLines = [];

            // DR: Cash (money received)
            $journalLines[] = [
                'account_id' => $cashAccount->Id,
                'debit' => $fee->amount,
                'credit' => 0,
                'narrative' => "Insurance fee collected",
            ];

            // CR: Insurance Payable (liability - insurance fund balance)
            $journalLines[] = [
                'account_id' => $insuranceLiabilityAccount->Id,
                'debit' => 0,
                'credit' => $fee->amount,
                'narrative' => "Insurance payable - {$feeType->name}",
            ];

            // Post the journal entry
            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Insurance fee GL entry posted', [
                'fee_id' => $fee->id,
                'journal_number' => $journal->journal_number,
                'amount' => $fee->amount,
                'member' => $memberName,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post insurance fee GL entry', [
                'fee_id' => $fee->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Helper method to get cash account by payment type
     * Used by repayments, cash security, and fees
     * 
     * @param int $paymentType 1=Cash, 2=Mobile Money, 3=Bank
     * @param string $paymentMethod Optional payment method string
     * @param string $paymentSource Optional payment source string
     */
    private function getCashAccountByPaymentMethod($paymentType, $paymentMethod = null, $paymentSource = null)
    {
        $paymentMethod = strtolower($paymentMethod ?? '');
        $paymentSource = strtolower($paymentSource ?? '');
        
        // Type 1 = Cash on Hand
        if ($paymentType == 1) {
            $cashAccount = SystemAccount::where('code', '10000')
                ->whereNull('sub_code')
                ->first();
            if ($cashAccount) return $cashAccount;
        }
        
        // Type 2 = Mobile Money
        if ($paymentType == 2 || str_contains($paymentMethod, 'mobile') || str_contains($paymentSource, 'mobile')) {
            $cashAccount = SystemAccount::where('code', '10000')
                ->where('sub_code', '10040')
                ->first();
            if ($cashAccount) return $cashAccount;
        }
        
        // Type 3 = Bank
        if ($paymentType == 3 || str_contains($paymentMethod, 'bank') || str_contains($paymentSource, 'bank')) {
            // Try to detect specific bank
            if (str_contains($paymentMethod, 'stanbic') || str_contains($paymentSource, 'stanbic')) {
                $cashAccount = SystemAccount::where('code', '10000')
                    ->where('sub_code', '10030')
                    ->first();
            } elseif (str_contains($paymentMethod, 'absa') || str_contains($paymentSource, 'absa')) {
                $cashAccount = SystemAccount::where('code', '10000')
                    ->where('sub_code', '10020')
                    ->first();
            } else {
                // Generic bank - use Stanbic as default
                $cashAccount = SystemAccount::where('code', '10000')
                    ->where('sub_code', '10030')
                    ->first();
            }
            if ($cashAccount) return $cashAccount;
        }
        
        // Default to mobile money (most common)
        $cashAccount = SystemAccount::where('code', '10000')
            ->where('sub_code', '10040')
            ->first();
        
        if ($cashAccount) return $cashAccount;
        
        // Last resort: parent cash account
        return SystemAccount::where('code', '10000')
            ->whereNull('sub_code')
            ->first();
    }
}
