<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\SystemAccount;
use App\Models\Fund;
use App\Models\Disbursement;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\FeeType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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
            $codes = $this->productSubCodes($loan->product->name ?? '');
            $loanReceivableSubCode = $codes['lr'];
            
            // Find Loan Receivable sub-account for this product (11000 series = Gross Receivables)
            $loanReceivableAccount = SystemAccount::where('code', '11000')
                ->where('sub_code', $loanReceivableSubCode)
                ->first();

            if (!$loanReceivableAccount) {
                throw new \Exception("Loan Receivable sub-account {$loanReceivableSubCode} not found for product: {$loan->product->name}");
            }

            // Find Cash/Bank account based on payment type
            $cashAccount = $this->getCashAccountByPaymentType($disbursement);

            // Capture investor reference (inv_id from investment table)
            $invId = $disbursement->inv_id ?? null;

            // Prepare journal entry data
            $borrowerName = $this->borrowerName($loan);

            // determine transaction date from actual disbursement date first, then cap to today
            $transactionDate = $this->capToToday(
                $disbursement->disbursement_date ?? $disbursement->date_approved ?? $disbursement->created_at ?? null
            );

            $journalData = [
                'transaction_date' => $transactionDate->format('Y-m-d'),
                'reference_type' => 'Disbursement',
                'reference_id' => $disbursement->id,
                'narrative' => "Loan disbursement to {$borrowerName} - {$loan->code}",
                'cost_center_id' => $loan->branch_id ?? null,
                'product_id'     => $loan->product_type ?? null,
                'officer_id'     => $disbursement->added_by ?? $loan->added_by ?? $loan->assigned_to ?? null,
                'posted_by'      => $disbursement->added_by ?? auth()->id(),
                'fund_id'        => null,
                'inv_id'         => $invId,
            ];

            // MOP FAN two-step disbursement flow:
            //   Step 1A: DR FAN → CR Bank     (funds move from bank into FAN control account)
            //   Step 1B: DR Loan Receivable → CR FAN  (FAN releases funds to create the loan)
            $fanAccount = SystemAccount::where('code', '20010')->whereNull('sub_code')->first();
            if (!$fanAccount) {
                throw new \Exception("FAN account (20010) not found — cannot post disbursement");
            }

            $amount = $disbursement->amount;

            $journalLines = [
                // Step 1A — DR FAN (funds enter the control account)
                [
                    'account_id' => $fanAccount->Id,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narrative'  => "FAN: funds staged for disbursement",
                ],
                // Step 1A — CR Bank/Cash (bank pays out)
                [
                    'account_id' => $cashAccount->Id,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narrative'  => "Bank/cash disbursed to borrower",
                ],
                // Step 1B — DR Loan Receivable (loan asset created)
                [
                    'account_id' => $loanReceivableAccount->Id,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narrative'  => "Loan principal receivable created",
                ],
                // Step 1B — CR FAN (FAN cleared — net FAN movement = zero)
                [
                    'account_id' => $fanAccount->Id,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narrative'  => "FAN: cleared after loan creation",
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
        // Loan disbursements settle from institution bank via FAN, regardless of client payout channel.
        $paymentSource = $disbursement->payment_source ?? null;
        return $this->getCashAccountByPaymentMethod(3, null, $paymentSource);
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
            // Get product sub-codes for this product
            $codes = $this->productSubCodes($loan->product->name ?? '');

            // FAN account (mandatory transit for all repayment allocations)
            $fanAccount = SystemAccount::where('code', '20010')->whereNull('sub_code')->first();
            if (!$fanAccount) {
                throw new \Exception("FAN account (20010) not found — cannot post repayment");
            }

            // Interest Receivable sub-account (to clear the accrued interest asset)
            $interestReceivableAccount = SystemAccount::where('code', '11200')
                ->where('sub_code', $codes['ir'])
                ->first();
            
            // Loan Receivable sub-account
            $loanReceivableAccount = SystemAccount::where('code', '11000')
                ->where('sub_code', $codes['lr'])
                ->first();

            if (!$loanReceivableAccount) {
                throw new \Exception("Loan Receivable sub-account {$codes['lr']} not found for product: {$loan->product->name}");
            }

            // Interest Income fallback account (used only if no accrual was posted)
            $interestIncomeAccount = SystemAccount::where('code', '41000')
                ->where('sub_code', $codes['ii'])
                ->first();

            // Late Fee Income account
            $lateFeeAccount = SystemAccount::where('code', '42000')
                ->where('sub_code', '42020')
                ->first()
                ?? SystemAccount::where('code', '42000')->whereNull('sub_code')->first();

            // Cash/Bank account based on payment method
            $paymentMethod = $repayment->payment_method ?? null;
            $paymentSource = $repayment->source ?? null;
            // Repayment collections settle to institutional bank via FAN, even when payer uses mobile channel.
            $cashAccount   = $this->getCashAccountByPaymentMethod(3, $paymentMethod, $paymentSource);

            if (!$loanReceivableAccount || !$cashAccount) {
                throw new \Exception("Required accounts not found (Loan Receivable or Cash)");
            }

            $borrowerName       = $this->borrowerName($loan);
            $repTransactionDate = $this->capToToday($repayment->date_created ?? null);
            $invId              = $this->resolveInvId($loan);

            // Idempotency guard: do not post a second active repayment JE for the same repayment.
            $existingRepaymentJE = JournalEntry::where('reference_type', 'Repayment')
                ->where('reference_id', $repayment->id)
                ->where('status', '!=', 'reversed')
                ->orderByDesc('Id')
                ->first();
            if ($existingRepaymentJE) {
                Log::warning('Skipped duplicate repayment GL posting', [
                    'repayment_id' => $repayment->id,
                    'existing_journal_id' => $existingRepaymentJE->Id,
                    'existing_journal_number' => $existingRepaymentJE->journal_number,
                ]);
                return $existingRepaymentJE;
            }

            $journalData = [
                'transaction_date' => $repTransactionDate->format('Y-m-d'),
                'reference_type'   => 'Repayment',
                'reference_id'     => $repayment->id,
                'narrative'        => "Loan repayment from {$borrowerName} - {$loan->code}",
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_type ?? null,
                'officer_id'       => $repayment->added_by ?? $loan->added_by ?? $loan->assigned_to ?? null,
                'posted_by'        => $repayment->added_by ?? auth()->id(),
                'fund_id'          => null,
                'inv_id'           => $invId,
            ];

            // Break down repayment using the agreed waterfall:
            // Interest/Principal first (up to schedule due), late fee only from excess over P+I.
            $schedule = \App\Models\LoanSchedule::find($repayment->schedule_id ?? null);
            $repaymentDate = $this->capToToday($repayment->date_created ?? null);
            $allocation = $this->allocateRepaymentBreakdownForJournal($repayment, $loan, $schedule, $repaymentDate);

            $principal = $allocation['principal'];
            $interest  = $allocation['interest'];
            $penalty   = $allocation['penalty'];
            $totalReceived = $allocation['total'];

            // ── MOP FAN repayment flow ────────────────────────────────────────────
            // Step 2: DR Bank → CR FAN   (cash received, parked in FAN)
            // Step 3a: DR FAN → CR Interest Receivable / Interest Income  (clear interest)
            // Step 3b: DR FAN → CR Loan Receivable  (reduce principal)
            // Step 3c: DR FAN → CR Late Fee Income  (settle penalty, if any)
            // Net FAN movement = zero

            $journalLines = [];

            // Step 2 — DR Bank (cash in)
            $journalLines[] = [
                'account_id' => $cashAccount->Id,
                'debit'      => $totalReceived,
                'credit'     => 0,
                'narrative'  => "Cash received from borrower via FAN",
            ];

            // Step 2 — CR FAN (park total in FAN)
            $journalLines[] = [
                'account_id' => $fanAccount->Id,
                'debit'      => 0,
                'credit'     => $totalReceived,
                'narrative'  => "FAN: receipt staged for allocation",
            ];

            // Step 3a — Interest allocation
            if ($interest > 0) {
                // Prefer clearing the Interest Receivable (accrual already posted).
                // Fall back to booking Interest Income directly if no accrual account exists.
                $interestClearAccount = $interestReceivableAccount ?? $interestIncomeAccount;
                $interestNarrative    = $interestReceivableAccount
                    ? "FAN: clears accrued interest receivable"
                    : "FAN: interest income (no prior accrual)";

                // DR FAN
                $journalLines[] = [
                    'account_id' => $fanAccount->Id,
                    'debit'      => $interest,
                    'credit'     => 0,
                    'narrative'  => $interestNarrative,
                ];
                // CR Interest Receivable (or Income fallback)
                $journalLines[] = [
                    'account_id' => $interestClearAccount->Id,
                    'debit'      => 0,
                    'credit'     => $interest,
                    'narrative'  => $interestNarrative,
                ];
            }

            // Step 3b — Principal allocation
            if ($principal > 0) {
                // DR FAN
                $journalLines[] = [
                    'account_id' => $fanAccount->Id,
                    'debit'      => $principal,
                    'credit'     => 0,
                    'narrative'  => "FAN: allocates principal repayment",
                ];
                // CR Loan Receivable
                $journalLines[] = [
                    'account_id' => $loanReceivableAccount->Id,
                    'debit'      => 0,
                    'credit'     => $principal,
                    'narrative'  => "Loan principal reduced",
                ];
            }

            // Step 3c — Late fee / penalty allocation
            if ($penalty > 0 && $lateFeeAccount) {
                // DR FAN
                $journalLines[] = [
                    'account_id' => $fanAccount->Id,
                    'debit'      => $penalty,
                    'credit'     => 0,
                    'narrative'  => "FAN: allocates late penalty",
                ];
                // CR Late Fee Income
                $journalLines[] = [
                    'account_id' => $lateFeeAccount->Id,
                    'debit'      => 0,
                    'credit'     => $penalty,
                    'narrative'  => "Late payment penalty income",
                ];
            }

            // Post the journal entry
            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Repayment GL entry posted', [
                'repayment_id'  => $repayment->id,
                'journal_number'=> $journal->journal_number,
                'total_amount'  => $totalReceived,
                'breakdown'     => compact('principal', 'interest', 'penalty'),
                'interest_mode' => $interestReceivableAccount ? 'clears_receivable' : 'direct_income',
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post repayment GL entry', [
                'repayment_id' => $repayment->id,
                'error'        => $e->getMessage(),
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
            $resolvedFeeType = $this->resolveFeeType($fee, $feeType);
            $feeTypeName = $resolvedFeeType->name ?? 'Fee';
            $feeName = strtolower($feeTypeName);
            
            // Check if this is a security deposit (LIABILITY, not income)
            if (stripos($feeName, 'security') !== false) {
                // Use the dedicated cash security posting method
                return $this->postCashSecurityEntry($fee);
            }
            
            // Check if this is insurance (LIABILITY, not income)
            if (stripos($feeName, 'insurance') !== false) {
                return $this->postInsuranceFeeEntry($fee, $resolvedFeeType);
            }
            
            // 1. Determine the correct income account based on fee type
            $incomeAccount = $this->getFeeIncomeAccount($resolvedFeeType);
            
            if (!$incomeAccount) {
                throw new \Exception("Fee income account not found for fee type: {$feeTypeName}");
            }

            // 2. Settlement account is always bank regardless of capture channel
            $paymentMethod = $fee->payment_method ?? null;
            $paymentSource = $fee->payment_source ?? $fee->source ?? null;
            $cashAccount = $this->getCashAccountByPaymentMethod(3, $paymentMethod, $paymentSource);

            if (!$cashAccount) {
                throw new \Exception("Cash/Bank account not found");
            }

            // 3. Get member/loan details
            $member = \App\Models\Member::find($fee->member_id);
            $loan = null;
            if ($fee->loan_id) {
                $loan = \App\Models\PersonalLoan::with('product', 'branch')->find($fee->loan_id);
            }

            $memberName = $member ? trim(($member->fname ?? '') . ' ' . ($member->lname ?? '')) : 'Unknown Member';

            // 4. Create journal entry
            $transactionDate = $fee->created_at ?? ($fee->datecreated ?? date('Y-m-d'));
            if ($transactionDate instanceof Carbon) {
                $transactionDate = $transactionDate->format('Y-m-d');
            }

            // MOP §1.3 FAN — fees must pass through FAN before allocation
            $fanAccount = SystemAccount::where('code', '20010')->whereNull('sub_code')->first();
            if (!$fanAccount) {
                throw new \Exception("FAN account (20010) not found — cannot post fee collection");
            }

            // Capture investor reference from the loan's linked disbursement
            $invId = $this->resolveInvId($loan);

            $journalData = [
                'transaction_date' => $transactionDate,
                'reference_type'   => 'Fee Collection',
                'reference_id'     => $fee->id,
                'narrative'        => "Fee payment: {$feeTypeName} from {$memberName}" . ($loan ? " - Loan {$loan->code}" : ''),
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_type ?? null,
                'officer_id'       => $fee->added_by ?? null,
                'posted_by'        => $fee->added_by ?? auth()->id(),
                'fund_id'          => null,
                'inv_id'           => $invId,
            ];

            $journalLines = [
                // Step A — DR Cash → CR FAN (money received, parked in FAN)
                [
                    'account_id' => $cashAccount->Id,
                    'debit'      => $fee->amount,
                    'credit'     => 0,
                    'narrative'  => "Cash received — {$feeTypeName}",
                ],
                [
                    'account_id' => $fanAccount->Id,
                    'debit'      => 0,
                    'credit'     => $fee->amount,
                    'narrative'  => "FAN: fee receipt staged",
                ],
                // Step B — DR FAN → CR Fee Income (allocate from FAN)
                [
                    'account_id' => $fanAccount->Id,
                    'debit'      => $fee->amount,
                    'credit'     => 0,
                    'narrative'  => "FAN: allocates fee to income",
                ],
                [
                    'account_id' => $incomeAccount->Id,
                    'debit'      => 0,
                    'credit'     => $fee->amount,
                    'narrative'  => "Fee income — {$feeTypeName}",
                ],
            ];

            // Post the journal entry
            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Fee collection GL entry posted', [
                'fee_id'         => $fee->id,
                'journal_number' => $journal->journal_number,
                'amount'         => $fee->amount,
                'fee_type'       => $feeTypeName,
                'income_account' => $incomeAccount->code . ' - ' . $incomeAccount->name,
                'cash_account'   => $cashAccount->code . ' - ' . $cashAccount->name,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post fee collection GL entry', [
                'fee_id' => $fee->id,
                'fee_type' => $feeTypeName ?? 'Unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Resolve fee type payload to an object with at least a name property.
     */
    private function resolveFeeType($fee, $feeType)
    {
        if (is_object($feeType) && isset($feeType->name)) {
            return $feeType;
        }

        if (is_numeric($feeType)) {
            $found = FeeType::find((int) $feeType);
            if ($found) {
                return $found;
            }
        }

        if (is_string($feeType) && trim($feeType) !== '') {
            $found = FeeType::where('name', $feeType)->first();
            if ($found) {
                return $found;
            }

            return (object) ['name' => $feeType];
        }

        if (isset($fee->feeType) && $fee->feeType && isset($fee->feeType->name)) {
            return $fee->feeType;
        }

        if (isset($fee->fees_type_id) && $fee->fees_type_id) {
            $found = FeeType::find((int) $fee->fees_type_id);
            if ($found) {
                return $found;
            }
        }

        return (object) ['name' => 'Fee'];
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

        // Affiliation / License / Restructuring / Individual affiliation → Administration Fees (42050)
        if (stripos($feeName, 'affiliation') !== false ||
            stripos($feeName, 'license') !== false ||
            stripos($feeName, 'restructur') !== false ||
            stripos($feeName, 'individual') !== false) {
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

        // Default: Route all unrecognised pre-disbursement fees to Administration Fees (42050)
        // rather than the bare parent account 42000
        return SystemAccount::where('code', '42000')
            ->where('sub_code', '42050')
            ->first();
    }

    /**
     * Post journal entry for cash security deposit
     * 
    * Cash security is a LIABILITY (refundable to member when loan is settled)
    * MOP flow: DR Bank -> CR FAN, then DR FAN -> CR Cash Security Liability
     */
    public function postCashSecurityEntry($cashSecurity)
    {
        try {
            // 1. Settlement account is always bank regardless of capture channel
            $paymentMethod = $cashSecurity->payment_method ?? null;
            $paymentSource = $cashSecurity->payment_source ?? $cashSecurity->source ?? null;
            $cashAccount = $this->getCashAccountByPaymentMethod(3, $paymentMethod, $paymentSource);

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

            $fanAccount = SystemAccount::where('code', '20010')->whereNull('sub_code')->first();
            if (!$fanAccount) {
                throw new \Exception("FAN account (20010) not found — cannot post cash security");
            }

            // 3. Get member/loan details
            $member = \App\Models\Member::find($cashSecurity->member_id);
            $loan = null;
            if (isset($cashSecurity->loan_id) && $cashSecurity->loan_id) {
                $loan = \App\Models\PersonalLoan::with('product', 'branch')->find($cashSecurity->loan_id);
            }
            
            $memberName = $member ? trim(($member->fname ?? '') . ' ' . ($member->lname ?? '')) : 'Unknown Member';

            // Capture investor reference from the loan's linked disbursement
            $invId = $this->resolveInvId($loan);

            $txDate = $cashSecurity->created_at ?? ($cashSecurity->datecreated ?? date('Y-m-d'));
            if ($txDate instanceof Carbon) { $txDate = $txDate->format('Y-m-d'); }

            $journalData = [
                'transaction_date' => $txDate,
                'reference_type'   => 'Cash Security',
                'reference_id'     => $cashSecurity->id,
                'narrative'        => "Cash security deposit from {$memberName}" . ($loan ? " - Loan {$loan->code}" : ''),
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_type ?? null,
                'officer_id'       => $cashSecurity->added_by ?? null,
                'posted_by'        => $cashSecurity->added_by ?? auth()->id(),
                'fund_id'          => null,
                'inv_id'           => $invId,
            ];

            $journalLines = [];

            // Step A — DR Bank/Cash settlement account
            $journalLines[] = [
                'account_id' => $cashAccount->Id,
                'debit' => $cashSecurity->amount,
                'credit' => 0,
                'narrative' => "Cash security deposit received",
            ];

            // Step A — CR FAN
            $journalLines[] = [
                'account_id' => $fanAccount->Id,
                'debit' => 0,
                'credit' => $cashSecurity->amount,
                'narrative' => "FAN: cash security receipt staged",
            ];

            // Step B — DR FAN
            $journalLines[] = [
                'account_id' => $fanAccount->Id,
                'debit' => $cashSecurity->amount,
                'credit' => 0,
                'narrative' => "FAN: allocates cash security to liability",
            ];

            // Step B — CR Cash Security Liability
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
    * MOP flow: DR Bank -> CR FAN, then DR FAN -> CR Insurance Payable
     */
    public function postInsuranceFeeEntry($fee, $feeType)
    {
        try {
            $feeTypeName = $feeType->name ?? 'Insurance Fee';
            // 1. Settlement account is always bank regardless of capture channel
            $paymentMethod = $fee->payment_method ?? null;
            $paymentSource = $fee->payment_source ?? $fee->source ?? null;
            $cashAccount = $this->getCashAccountByPaymentMethod(3, $paymentMethod, $paymentSource);

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

            $fanAccount = SystemAccount::where('code', '20010')->whereNull('sub_code')->first();
            if (!$fanAccount) {
                throw new \Exception("FAN account (20010) not found — cannot post insurance fee");
            }

            // 3. Get member/loan details
            $member = \App\Models\Member::find($fee->member_id);
            $loan = null;
            if ($fee->loan_id) {
                $loan = \App\Models\PersonalLoan::with('product', 'branch')->find($fee->loan_id);
            }
            
            $memberName = $member ? trim(($member->fname ?? '') . ' ' . ($member->lname ?? '')) : 'Unknown Member';

            // Capture investor reference from the loan's linked disbursement
            $invId = $this->resolveInvId($loan);

            // 4. Create journal entry
            $transactionDate = $fee->created_at ?? ($fee->datecreated ?? date('Y-m-d'));
            if ($transactionDate instanceof Carbon) {
                $transactionDate = $transactionDate->format('Y-m-d');
            }

            $journalData = [
                'transaction_date' => $transactionDate,
                'reference_type'   => 'Insurance Fee',
                'reference_id'     => $fee->id,
                'narrative'        => "Insurance fee from {$memberName}" . ($loan ? " - Loan {$loan->code}" : ''),
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_type ?? null,
                'officer_id'       => $fee->added_by ?? null,
                'posted_by'        => $fee->added_by ?? auth()->id(),
                'fund_id'          => null,
                'inv_id'           => $invId,
            ];

            $journalLines = [];

            // Step A — DR Bank/Cash settlement account
            $journalLines[] = [
                'account_id' => $cashAccount->Id,
                'debit' => $fee->amount,
                'credit' => 0,
                'narrative' => "Insurance fee collected",
            ];

            // Step A — CR FAN
            $journalLines[] = [
                'account_id' => $fanAccount->Id,
                'debit' => 0,
                'credit' => $fee->amount,
                'narrative' => "FAN: insurance receipt staged",
            ];

            // Step B — DR FAN
            $journalLines[] = [
                'account_id' => $fanAccount->Id,
                'debit' => $fee->amount,
                'credit' => 0,
                'narrative' => "FAN: allocates insurance to liability",
            ];

            // Step B — CR Insurance Payable
            $journalLines[] = [
                'account_id' => $insuranceLiabilityAccount->Id,
                'debit' => 0,
                'credit' => $fee->amount,
                'narrative' => "Insurance payable - {$feeTypeName}",
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
     * Post journal entry for savings deposits made from member profile.
     *
     * DR: Cash/Bank/Mobile wallet
     * CR: Client Savings Deposits liability (21010)
     */
    public function postSavingsDepositEntry($saving, $member = null)
    {
        try {
            $depositAmount = (float) ($saving->value ?? 0);
            if ($depositAmount <= 0) {
                throw new \Exception('Invalid savings deposit amount');
            }

            if (!$member && !empty($saving->member_id)) {
                $member = \App\Models\Member::find($saving->member_id);
            }

            $paymentMethod = $saving->payment_method ?? null;
            $paymentSource = $saving->payment_source ?? $saving->source ?? null;
            $cashAccount = $this->getCashAccountByPaymentMethod(3, $paymentMethod, $paymentSource);

            $savingsLiabilityAccount = SystemAccount::where('code', '21000')
                ->where('sub_code', '21010')
                ->first();

            if (!$savingsLiabilityAccount) {
                $savingsLiabilityAccount = SystemAccount::where('code', '21000')
                    ->whereNull('sub_code')
                    ->first();
            }

            if (!$cashAccount || !$savingsLiabilityAccount) {
                throw new \Exception('Required accounts not found (Cash or Client Savings Deposits)');
            }

            $memberName = $member ? trim(($member->fname ?? '') . ' ' . ($member->lname ?? '')) : 'Unknown Member';
            $transactionDate = $saving->datecreated ?? ($saving->created_at ?? $saving->sdate ?? date('Y-m-d'));
            if ($transactionDate instanceof Carbon) {
                $transactionDate = $transactionDate->format('Y-m-d');
            }

            $journalData = [
                'transaction_date' => $transactionDate,
                'reference_type' => 'Savings Deposit',
                'reference_id' => $saving->id,
                'narrative' => "Savings deposit from {$memberName} - {$saving->code}",
                'cost_center_id' => $saving->branch_id ?? null,
                'officer_id' => $saving->added_by ?? null,
                'posted_by' => $saving->added_by ?? auth()->id(),
            ];

            $journalLines = [
                [
                    'account_id' => $cashAccount->Id,
                    'debit' => $depositAmount,
                    'credit' => 0,
                    'narrative' => 'Savings deposit received',
                ],
                [
                    'account_id' => $savingsLiabilityAccount->Id,
                    'debit' => 0,
                    'credit' => $depositAmount,
                    'narrative' => 'Client savings deposits liability',
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Savings deposit GL entry posted', [
                'saving_id' => $saving->id,
                'journal_number' => $journal->journal_number,
                'amount' => $depositAmount,
            ]);

            return $journal;
        } catch (\Exception $e) {
            Log::error('Failed to post savings deposit GL entry', [
                'saving_id' => $saving->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
        $paymentType = is_numeric($paymentType) ? (int) $paymentType : null;
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
        if ($paymentType === 2) {
            $cashAccount = SystemAccount::where('code', '10000')
                ->where('sub_code', '10040')
                ->first();
            if ($cashAccount) return $cashAccount;
        }
        
        // Type 3 = Bank
        if ($paymentType === 3) {
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

        // Infer payment channel only when payment type is not explicit
        if (str_contains($paymentMethod, 'mobile') || str_contains($paymentSource, 'mobile')) {
            $cashAccount = SystemAccount::where('code', '10000')
                ->where('sub_code', '10040')
                ->first();
            if ($cashAccount) return $cashAccount;
        }

        if (str_contains($paymentMethod, 'bank') || str_contains($paymentSource, 'bank')) {
            if (str_contains($paymentMethod, 'absa') || str_contains($paymentSource, 'absa')) {
                $cashAccount = SystemAccount::where('code', '10000')
                    ->where('sub_code', '10020')
                    ->first();
            } else {
                $cashAccount = SystemAccount::where('code', '10000')
                    ->where('sub_code', '10030')
                    ->first();
            }
            if ($cashAccount) return $cashAccount;
        }
        
        // Default to bank (Stanbic) when source/method is ambiguous
        $cashAccount = SystemAccount::where('code', '10000')
            ->where('sub_code', '10030')
            ->first();

        if ($cashAccount) return $cashAccount;

        // Last resort: parent cash account
        return SystemAccount::where('code', '10000')
            ->whereNull('sub_code')
            ->first();
    }

    /**
     * Post a periodic interest accrual entry for a loan schedule instalment.
     *
     * MOP §3 — Accrual Accounting:
     *   DR Interest Receivable (11210/11220/11230/11240) — asset increases
     *   CR Interest Income     (41010/41020/41030/41040) — income recognised
     *
     * This is called by the daily/weekly accrual scheduler; it must be called
     * BEFORE postRepaymentEntry so the receivable exists when cleared.
     *
     * @param  \App\Models\Loan          $loan
     * @param  \App\Models\LoanSchedule  $schedule
     * @param  \Carbon\Carbon|string     $accrualDate   Date interest was earned
     * @return \App\Models\JournalEntry|null
     */
    public function postInterestAccrualEntry($loan, $schedule, $accrualDate = null)
    {
        try {
            $interestAmount = (float) ($schedule->interest ?? 0);

            if ($interestAmount <= 0) {
                Log::info('Interest accrual skipped — zero interest on schedule', [
                    'loan_id'     => $loan->id,
                    'schedule_id' => $schedule->id,
                ]);
                return null;
            }

            // Prevent duplicate accrual for the same schedule
            $exists = \App\Models\JournalEntry::where('reference_type', 'Interest Accrual')
                ->where('reference_id', $schedule->id)
                ->exists();

            if ($exists) {
                Log::info('Interest accrual already posted for schedule', [
                    'schedule_id' => $schedule->id,
                ]);
                return null;
            }

            $productName = strtolower($loan->product->name ?? '');
            $codes = $this->productSubCodes($loan->product->name ?? '');

            $interestReceivableAccount = SystemAccount::where('code', '11200')
                ->where('sub_code', $codes['ir'])
                ->first();

            $interestIncomeAccount = SystemAccount::where('code', '41000')
                ->where('sub_code', $codes['ii'])
                ->first();

            if (!$interestReceivableAccount || !$interestIncomeAccount) {
                throw new \Exception("Interest accrual accounts not found (IR: {$codes['ir']}, II: {$codes['ii']})");
            }

            $accrualDate = $this->capToToday($accrualDate ?? Carbon::today());

            $borrowerName = $this->borrowerName($loan);
            $invId        = $this->resolveInvId($loan);

            $journalData = [
                'transaction_date' => $accrualDate->format('Y-m-d'),
                'reference_type'   => 'Interest Accrual',
                'reference_id'     => $schedule->id,
                'narrative'        => "Interest accrued — {$borrowerName} — {$loan->code} (due {$schedule->payment_date})",
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_type ?? null,
                'officer_id'       => $loan->added_by ?? $loan->assigned_to ?? null,
                'posted_by'        => auth()->id() ?? $loan->created_by ?? 1,
                'fund_id'          => null,
                'inv_id'           => $invId,
            ];

            $journalLines = [
                [
                    'account_id' => $interestReceivableAccount->Id,
                    'debit'      => $interestAmount,
                    'credit'     => 0,
                    'narrative'  => "Interest earned — accrued receivable",
                ],
                [
                    'account_id' => $interestIncomeAccount->Id,
                    'debit'      => 0,
                    'credit'     => $interestAmount,
                    'narrative'  => "Interest income recognised",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Interest accrual GL entry posted', [
                'loan_id'        => $loan->id,
                'schedule_id'    => $schedule->id,
                'accrual_amount' => $interestAmount,
                'journal_number' => $journal->journal_number,
                'ir_account'     => $interestReceivableAccount->code . '.' . $codes['ir'],
                'ii_account'     => $interestIncomeAccount->code . '.' . $codes['ii'],
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post interest accrual GL entry', [
                'loan_id'     => $loan->id ?? null,
                'schedule_id' => $schedule->id ?? null,
                'error'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Return product-specific GL sub-codes for a given product name.
     * Used by NPL, restructure, waiver, and write-off methods to avoid
     * repeating the same match blocks.
     *
     * Keys returned:
     *   lr  — Loan Receivable sub-code        (11010/11020/11030/11040)
     *   ir  — Interest Receivable sub-code     (11210/11220/11230/11240)
     *   ii  — Interest Income sub-code         (41010/41020/41030/41040)
     *   llp — Loan Loss Provision sub-code     (11110/11120/11130/11140)
     */
    /**
     * Resolve fund/investor reference for a loan.
     * Looks for the most recent disbursement with an inv_id attached.
     */
    private function resolveInvId($loan): ?int
    {
        if (!$loan || !$loan->id) {
            return null;
        }
        $disb = \App\Models\Disbursement::where('loan_id', $loan->id)
            ->whereNotNull('inv_id')
            ->orderByDesc('id')
            ->first();
        return $disb->inv_id ?? null;
    }

    /**
     * Return borrower display name for journal narratives.
     */
    private function borrowerName($loan): string
    {
        if ($loan->member ?? null) {
            return trim(($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? ''));
        }
        return $loan->group->name ?? 'Unknown';
    }

    /**
     * Parse $raw to a Carbon date, then cap future dates to today.
     * Accepts Carbon instances, date strings, or null (returns today).
     */
    private function capToToday($raw): Carbon
    {
        if ($raw instanceof Carbon) {
            $date = $raw;
        } elseif (is_string($raw) && $raw !== '') {
            $date = Carbon::parse($raw);
        } else {
            return Carbon::today();
        }
        return $date->greaterThan(Carbon::today()) ? Carbon::today() : $date;
    }

    private function productSubCodes(string $productName): array
    {
        $p         = strtolower($productName);
        $isSchool  = str_contains($p, 'school')  || str_contains($p, 'student') || str_contains($p, 'tuition');
        $isSalary  = str_contains($p, 'staff')   || str_contains($p, 'salary')  || str_contains($p, 'employee');
        $isHousing = str_contains($p, 'housing') || str_contains($p, 'mortgage')|| str_contains($p, 'property');

        return [
            'lr'  => $isSchool ? '11010' : ($isSalary ? '11030' : ($isHousing ? '11040' : '11020')),
            'ir'  => $isSchool ? '11210' : ($isSalary ? '11230' : ($isHousing ? '11240' : '11220')),
            'ii'  => $isSchool ? '41010' : ($isSalary ? '41030' : ($isHousing ? '41040' : '41020')),
            'llp' => $isSchool ? '11110' : ($isSalary ? '11130' : ($isHousing ? '11140' : '11120')),
        ];
    }

    /**
     * Calculate net late fee due for a schedule at a specific date.
     * Uses repayment-date periods and subtracts waived late fees (status=2).
     */
    private function calculateScheduleLateFeeAtDate($schedule, $loan, Carbon $asOfDate): float
    {
        if (!$schedule) {
            return 0.0;
        }

        $dueDate = $this->parseSchedulePaymentDate($schedule->payment_date ?? null);
        if (!$dueDate || $asOfDate->lessThanOrEqualTo($dueDate)) {
            return 0.0;
        }

        $daysLate = $dueDate->diffInDays($asOfDate);
        $periodType = (string) ($loan->product->period_type ?? '1');

        if ($periodType === '1') {
            $periodsOverdue = (int) ceil($daysLate / 7);
        } elseif ($periodType === '2') {
            $periodsOverdue = (int) ceil($daysLate / 30);
        } elseif ($periodType === '3') {
            $periodsOverdue = (int) $daysLate;
        } else {
            $periodsOverdue = (int) ceil($daysLate / 7);
        }

        if ($periodsOverdue <= 0) {
            return 0.0;
        }

        $baseAmount = (float) ($schedule->principal ?? 0) + (float) ($schedule->interest ?? 0);
        $grossLateFee = ($baseAmount * 0.06) * $periodsOverdue;

        $waivedLateFees = (float) DB::table('late_fees')
            ->where('schedule_id', $schedule->id)
            ->where('status', 2)
            ->sum('amount');

        return max(0.0, $grossLateFee - $waivedLateFees);
    }

    /**
     * Allocate repayment components for GL posting.
     *
     * Business rule:
     * - P+I is settled first (capped by schedule remaining balances)
     * - Late fee is paid only from amount above remaining P+I
     */
    private function allocateRepaymentBreakdownForJournal($repayment, $loan, $schedule, Carbon $repaymentDate): array
    {
        $amount = max(0.0, (float) ($repayment->amount ?? 0));
        if ($amount <= 0) {
            return ['principal' => 0.0, 'interest' => 0.0, 'penalty' => 0.0, 'total' => 0.0];
        }

        // Respect explicit split if the repayment already carries it.
        $explicitPrincipal = max(0.0, (float) ($repayment->principal ?? 0));
        $explicitInterest  = max(0.0, (float) ($repayment->interest ?? 0));
        $explicitPenalty   = max(0.0, (float) ($repayment->penalty ?? 0));
        $explicitTotal     = $explicitPrincipal + $explicitInterest + $explicitPenalty;
        if ($explicitTotal > 0) {
            $scale = ($explicitTotal > $amount) ? ($amount / $explicitTotal) : 1.0;
            $p = $explicitPrincipal * $scale;
            $i = $explicitInterest * $scale;
            $f = $explicitPenalty * $scale;
            return [
                'principal' => round($p, 2),
                'interest'  => round($i, 2),
                'penalty'   => round($f, 2),
                'total'     => round($p + $i + $f, 2),
            ];
        }

        // No schedule means we cannot derive a reliable late-fee/interest split.
        if (!$schedule) {
            return ['principal' => round($amount, 2), 'interest' => 0.0, 'penalty' => 0.0, 'total' => round($amount, 2)];
        }

        $remainingInterest = max(0.0, (float) ($schedule->interest ?? 0));
        $remainingPrincipal = max(0.0, (float) ($schedule->principal ?? 0));
        $paidLateFeesSoFar = 0.0;

        // Replay successful repayments for this schedule up to and including current row.
        // Use id ordering for deterministic behavior with legacy date formats.
        $allRepayments = \App\Models\Repayment::where('schedule_id', $schedule->id)
            ->where('amount', '>', 0)
            ->where(function ($q) {
                $q->where('status', 1)
                    ->orWhere('pay_status', 'SUCCESS')
                    ->orWhere(function ($x) {
                        $x->where('payment_status', 'Completed')
                            ->where(function ($y) {
                                $y->whereNull('pay_status')
                                    ->orWhereNotIn('pay_status', ['FAILED', 'INVALID']);
                            })
                            ->where(function ($y) {
                                $y->whereNull('status')->orWhere('status', '>=', 0);
                            });
                    });
            })
            ->orderBy('id')
            ->get();

        $interest = 0.0;
        $principal = 0.0;
        $penalty = 0.0;

        foreach ($allRepayments as $row) {
            $rowAmount = max(0.0, (float) ($row->amount ?? 0));
            if ($rowAmount <= 0) {
                continue;
            }

            $piRemaining = max(0.0, $remainingInterest + $remainingPrincipal);
            $piPortion = min($rowAmount, $piRemaining);

            $allocInterest = min($piPortion, $remainingInterest);
            $allocPrincipal = min(max(0.0, $piPortion - $allocInterest), $remainingPrincipal);

            $remainingInterest -= $allocInterest;
            $remainingPrincipal -= $allocPrincipal;

            $excess = max(0.0, $rowAmount - $piRemaining);
            $rowDate = $this->capToToday($row->date_created ?? null);
            $lateDueAtRowDate = $this->calculateScheduleLateFeeAtDate($schedule, $loan, $rowDate);
            $remainingLateAtRowDate = max(0.0, $lateDueAtRowDate - $paidLateFeesSoFar);
            $allocLate = min($excess, $remainingLateAtRowDate);
            $paidLateFeesSoFar += $allocLate;

            if ((int) $row->id === (int) ($repayment->id ?? 0)) {
                $interest = $allocInterest;
                $principal = $allocPrincipal;
                $penalty = $allocLate;
                break;
            }
        }

        // Keep JE fully allocated to paid cash in edge cases (e.g. stale schedule balances).
        $allocated = $principal + $interest + $penalty;
        if ($amount > $allocated) {
            $principal += ($amount - $allocated);
        }

        $total = $principal + $interest + $penalty;

        return [
            'principal' => round($principal, 2),
            'interest'  => round($interest, 2),
            'penalty'   => round($penalty, 2),
            'total'     => round($total, 2),
        ];
    }

    /**
     * Parse schedule payment date from either d-m-Y or Y-m-d style strings.
     */
    private function parseSchedulePaymentDate($raw): ?Carbon
    {
        if ($raw instanceof Carbon) {
            return $raw->copy()->startOfDay();
        }

        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $raw = trim($raw);
        try {
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $raw)) {
                return Carbon::createFromFormat('d-m-Y', $raw)->startOfDay();
            }

            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 5 — NPL & CREDIT RISK
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * MOP §3 Step 4 / §5 Step 2 — NPL Interest Transfer
     * When a loan becomes non-performing, move all accrued interest out of
     * Interest Receivable into Interest Suspense so it is no longer reported
     * as collectible income.
     *
     *   DR GL:11200/11250 (Interest Suspense – NPL)
     *   CR GL:11200/112xx (Interest Receivable – [product])
     *
     * @param  mixed  $loan            PersonalLoan or GroupLoan
     * @param  float  $accruedInterest Total interest receivable outstanding for this loan
     */
    public function postNplInterestTransferEntry($loan, float $accruedInterest)
    {
        try {
            if ($accruedInterest <= 0) {
                return null;
            }

            $codes = $this->productSubCodes($loan->product->name ?? '');

            $interestReceivableAccount = SystemAccount::where('code', '11200')
                ->where('sub_code', $codes['ir'])
                ->first();

            $interestSuspenseAccount = SystemAccount::where('code', '11200')
                ->where('sub_code', '11250')
                ->first();

            if (!$interestReceivableAccount || !$interestSuspenseAccount) {
                throw new \Exception("NPL interest transfer accounts not found (IR: {$codes['ir']}, ISU: 11250)");
            }

            $borrowerName = $loan->member
                ? ($loan->member->fname . ' ' . $loan->member->lname)
                : ($loan->group->name ?? 'Unknown');

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'NPL Interest Transfer',
                'reference_id'     => $loan->id,
                'narrative'        => "NPL: accrued interest moved to suspense — {$borrowerName} — {$loan->code}",
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_id ?? null,
                'officer_id'       => $loan->assigned_to ?? null,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $interestSuspenseAccount->Id,
                    'debit'      => $accruedInterest,
                    'credit'     => 0,
                    'narrative'  => "Interest Suspense – NPL",
                ],
                [
                    'account_id' => $interestReceivableAccount->Id,
                    'debit'      => 0,
                    'credit'     => $accruedInterest,
                    'narrative'  => "Interest Receivable cleared to suspense",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('NPL interest transfer GL entry posted', [
                'loan_id'        => $loan->id,
                'journal_number' => $journal->journal_number,
                'amount'         => $accruedInterest,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post NPL interest transfer GL entry', [
                'loan_id' => $loan->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * MOP §5 Step 3 — Loan Loss Provision (ECL)
     * Record a provision charge against the loan loss reserve.
     *
     *   DR GL:56100 (Loan Loss Provision Expense)
     *   CR GL:11100/111xx (Loan Loss Provision – contra-asset)
     *
     * @param  mixed  $loan
     * @param  float  $provisionAmount   Amount to provision (e.g. 100% of principal)
     */
    public function postNplProvisionEntry($loan, float $provisionAmount)
    {
        try {
            if ($provisionAmount <= 0) {
                return null;
            }

            $codes = $this->productSubCodes($loan->product->name ?? '');

            $provisionExpenseAccount = SystemAccount::where('code', '56100')
                ->whereNull('sub_code')
                ->first();

            $loanLossProvisionAccount = SystemAccount::where('code', '11100')
                ->where('sub_code', $codes['llp'])
                ->first()
                ?? SystemAccount::where('code', '11100')->whereNull('sub_code')->first();

            if (!$provisionExpenseAccount || !$loanLossProvisionAccount) {
                throw new \Exception("Provision accounts not found (56100 or 11100/{$codes['llp']})");
            }

            $borrowerName = $loan->member
                ? ($loan->member->fname . ' ' . $loan->member->lname)
                : ($loan->group->name ?? 'Unknown');

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'NPL Provision',
                'reference_id'     => $loan->id,
                'narrative'        => "ECL provision — {$borrowerName} — {$loan->code}",
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_id ?? null,
                'officer_id'       => $loan->assigned_to ?? null,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $provisionExpenseAccount->Id,
                    'debit'      => $provisionAmount,
                    'credit'     => 0,
                    'narrative'  => "Loan loss provision expense",
                ],
                [
                    'account_id' => $loanLossProvisionAccount->Id,
                    'debit'      => 0,
                    'credit'     => $provisionAmount,
                    'narrative'  => "Loan loss provision reserve",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('NPL provision GL entry posted', [
                'loan_id'        => $loan->id,
                'journal_number' => $journal->journal_number,
                'amount'         => $provisionAmount,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post NPL provision GL entry', [
                'loan_id' => $loan->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * MOP §2 Step 4 / §5 Step 4 — Loan Write-off
     * Eliminate uncollectable principal by drawing down the provision reserve.
     *
     *   DR GL:11100/111xx (Loan Loss Provision – contra-asset)
     *   CR GL:11000/110xx (Loan Receivable – principal)
     *
     * @param  mixed  $loan
     * @param  float  $writeOffAmount   Principal amount being written off
     */
    public function postLoanWriteOffEntry($loan, float $writeOffAmount)
    {
        try {
            if ($writeOffAmount <= 0) {
                return null;
            }

            $codes = $this->productSubCodes($loan->product->name ?? '');

            $loanLossProvisionAccount = SystemAccount::where('code', '11100')
                ->where('sub_code', $codes['llp'])
                ->first()
                ?? SystemAccount::where('code', '11100')->whereNull('sub_code')->first();

            $loanReceivableAccount = SystemAccount::where('code', '11000')
                ->where('sub_code', $codes['lr'])
                ->first();

            if (!$loanLossProvisionAccount || !$loanReceivableAccount) {
                throw new \Exception("Write-off accounts not found (LLP: {$codes['llp']}, LR: {$codes['lr']})");
            }

            $borrowerName = $loan->member
                ? ($loan->member->fname . ' ' . $loan->member->lname)
                : ($loan->group->name ?? 'Unknown');

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'Loan Write-off',
                'reference_id'     => $loan->id,
                'narrative'        => "Loan written off — {$borrowerName} — {$loan->code}",
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_id ?? null,
                'officer_id'       => $loan->assigned_to ?? null,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $loanLossProvisionAccount->Id,
                    'debit'      => $writeOffAmount,
                    'credit'     => 0,
                    'narrative'  => "Provision drawn down for write-off",
                ],
                [
                    'account_id' => $loanReceivableAccount->Id,
                    'debit'      => 0,
                    'credit'     => $writeOffAmount,
                    'narrative'  => "Loan receivable eliminated",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Loan write-off GL entry posted', [
                'loan_id'        => $loan->id,
                'journal_number' => $journal->journal_number,
                'amount'         => $writeOffAmount,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post loan write-off GL entry', [
                'loan_id' => $loan->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 4 — RESTRUCTURED LOANS (CWRL)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * MOP §4 Step 1 — Loan Capitalisation (Restructure)
     * Capitalise principal + accrued interest + late fees into a single
     * Restructured Loan Receivable balance. Must occur in weeks 5–7.
     *
     *   DR GL:11000/11050 (Loan Receivable – Restructured)
     *   CR GL:11000/110xx (Loan Receivable – original product)
     *   CR GL:11200/112xx (Interest Receivable)
     *   CR GL:11300       (Late Fee Receivable)   — only if $lateFees > 0
     *
     * @param  mixed  $loan
     * @param  float  $principalOutstanding
     * @param  float  $accruedInterest
     * @param  float  $lateFees
     */
    public function postLoanRestructureEntry($loan, float $principalOutstanding, float $accruedInterest, float $lateFees = 0.0)
    {
        try {
            $total = $principalOutstanding + $accruedInterest + $lateFees;
            if ($total <= 0) {
                return null;
            }

            $codes = $this->productSubCodes($loan->product->name ?? '');

            $restructuredLrAccount = SystemAccount::where('code', '11000')
                ->where('sub_code', '11050')
                ->first();

            $loanReceivableAccount = SystemAccount::where('code', '11000')
                ->where('sub_code', $codes['lr'])
                ->first();

            $interestReceivableAccount = SystemAccount::where('code', '11200')
                ->where('sub_code', $codes['ir'])
                ->first();

            $lateFeeReceivableAccount = SystemAccount::where('code', '11300')
                ->whereNull('sub_code')
                ->first();

            if (!$restructuredLrAccount || !$loanReceivableAccount || !$interestReceivableAccount) {
                throw new \Exception("Restructure accounts not found (CWRL: 11050, LR: {$codes['lr']}, IR: {$codes['ir']})");
            }

            $borrowerName = $loan->member
                ? ($loan->member->fname . ' ' . $loan->member->lname)
                : ($loan->group->name ?? 'Unknown');

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'Loan Restructure',
                'reference_id'     => $loan->id,
                'narrative'        => "CWRL capitalisation — {$borrowerName} — {$loan->code}",
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_id ?? null,
                'officer_id'       => $loan->assigned_to ?? null,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                // DR Restructured Loan Receivable (new consolidated balance)
                [
                    'account_id' => $restructuredLrAccount->Id,
                    'debit'      => $total,
                    'credit'     => 0,
                    'narrative'  => "CWRL: consolidated restructured balance",
                ],
                // CR original Loan Receivable (principal cleared)
                [
                    'account_id' => $loanReceivableAccount->Id,
                    'debit'      => 0,
                    'credit'     => $principalOutstanding,
                    'narrative'  => "CWRL: original principal capitalised",
                ],
                // CR Interest Receivable (accrued interest capitalised)
                [
                    'account_id' => $interestReceivableAccount->Id,
                    'debit'      => 0,
                    'credit'     => $accruedInterest,
                    'narrative'  => "CWRL: accrued interest capitalised",
                ],
            ];

            // CR Late Fee Receivable only if fees exist and account is available
            if ($lateFees > 0 && $lateFeeReceivableAccount) {
                $journalLines[] = [
                    'account_id' => $lateFeeReceivableAccount->Id,
                    'debit'      => 0,
                    'credit'     => $lateFees,
                    'narrative'  => "CWRL: late fees capitalised",
                ];
            }

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Loan restructure GL entry posted', [
                'loan_id'        => $loan->id,
                'journal_number' => $journal->journal_number,
                'total'          => $total,
                'breakdown'      => compact('principalOutstanding', 'accruedInterest', 'lateFees'),
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post loan restructure GL entry', [
                'loan_id' => $loan->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * MOP §4 Step 3 — Interest Waiver (compliant restructured client)
     * Write off a portion of accrued interest as a goodwill waiver.
     *
     *   DR GL:56200 (Interest Waiver Expense)
     *   CR GL:11200/112xx (Interest Receivable)
     *
     * @param  mixed  $loan
     * @param  float  $waiverAmount   Amount of interest being waived
     */
    public function postInterestWaiverEntry($loan, float $waiverAmount)
    {
        try {
            if ($waiverAmount <= 0) {
                return null;
            }

            $codes = $this->productSubCodes($loan->product->name ?? '');

            $interestWaiverExpenseAccount = SystemAccount::where('code', '56200')
                ->whereNull('sub_code')
                ->first();

            $interestReceivableAccount = SystemAccount::where('code', '11200')
                ->where('sub_code', $codes['ir'])
                ->first();

            if (!$interestWaiverExpenseAccount || !$interestReceivableAccount) {
                throw new \Exception("Waiver accounts not found (56200 or IR: {$codes['ir']})");
            }

            $borrowerName = $loan->member
                ? ($loan->member->fname . ' ' . $loan->member->lname)
                : ($loan->group->name ?? 'Unknown');

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'Interest Waiver',
                'reference_id'     => $loan->id,
                'narrative'        => "Interest waiver — {$borrowerName} — {$loan->code}",
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_id ?? null,
                'officer_id'       => $loan->assigned_to ?? null,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $interestWaiverExpenseAccount->Id,
                    'debit'      => $waiverAmount,
                    'credit'     => 0,
                    'narrative'  => "Interest waiver expense (IWE)",
                ],
                [
                    'account_id' => $interestReceivableAccount->Id,
                    'debit'      => 0,
                    'credit'     => $waiverAmount,
                    'narrative'  => "Interest receivable waived",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Interest waiver GL entry posted', [
                'loan_id'        => $loan->id,
                'journal_number' => $journal->journal_number,
                'amount'         => $waiverAmount,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post interest waiver GL entry', [
                'loan_id' => $loan->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * MOP §4 Step 4 — Waiver Reversal (client re-defaults after waiver)
     * Reinstate previously waived interest back onto the loan.
     *
     *   DR GL:11200/112xx (Interest Receivable)
     *   CR GL:42000/42025 (Interest Waiver Reversal Income)
     *
     * @param  mixed  $loan
     * @param  float  $reversalAmount   Amount of previously waived interest being reinstated
     */
    public function postWaiverReversalEntry($loan, float $reversalAmount)
    {
        try {
            if ($reversalAmount <= 0) {
                return null;
            }

            $codes = $this->productSubCodes($loan->product->name ?? '');

            $interestReceivableAccount = SystemAccount::where('code', '11200')
                ->where('sub_code', $codes['ir'])
                ->first();

            $waiverReversalIncomeAccount = SystemAccount::where('code', '42000')
                ->where('sub_code', '42025')
                ->first();

            if (!$interestReceivableAccount || !$waiverReversalIncomeAccount) {
                throw new \Exception("Waiver reversal accounts not found (IR: {$codes['ir']}, IWR: 42025)");
            }

            $borrowerName = $loan->member
                ? ($loan->member->fname . ' ' . $loan->member->lname)
                : ($loan->group->name ?? 'Unknown');

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'Waiver Reversal',
                'reference_id'     => $loan->id,
                'narrative'        => "Waiver reversal — {$borrowerName} — {$loan->code}",
                'cost_center_id'   => $loan->branch_id ?? null,
                'product_id'       => $loan->product_id ?? null,
                'officer_id'       => $loan->assigned_to ?? null,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $interestReceivableAccount->Id,
                    'debit'      => $reversalAmount,
                    'credit'     => 0,
                    'narrative'  => "Interest receivable reinstated",
                ],
                [
                    'account_id' => $waiverReversalIncomeAccount->Id,
                    'debit'      => 0,
                    'credit'     => $reversalAmount,
                    'narrative'  => "Interest waiver reversal income (IWR)",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Waiver reversal GL entry posted', [
                'loan_id'        => $loan->id,
                'journal_number' => $journal->journal_number,
                'amount'         => $reversalAmount,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post waiver reversal GL entry', [
                'loan_id' => $loan->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 6 — CASH CONTROL (ZCL)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * MOP §6 Step 2 — Move Cash to Transit (Cash-in-Transit)
     * Officer hands physical cash to the CIT/transport for bank deposit.
     *
     *   DR GL:10100/10110 (Cash-in-Transit – EPR Clearing)
     *   CR GL:10000       (Cash on Hand)
     *
     * @param  float     $amount
     * @param  int|null  $branchId
     * @param  int|null  $officerId
     */
    public function postCashToTransitEntry(float $amount, ?int $branchId = null, ?int $officerId = null)
    {
        try {
            if ($amount <= 0) {
                return null;
            }

            $citAccount = SystemAccount::where('code', '10100')
                ->where('sub_code', '10110')
                ->first()
                ?? SystemAccount::where('code', '10100')->whereNull('sub_code')->first();

            $cashAccount = SystemAccount::where('code', '10000')
                ->whereNull('sub_code')
                ->first();

            if (!$citAccount || !$cashAccount) {
                throw new \Exception("Cash-to-transit accounts not found (10110 or parent 10000)");
            }

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'Cash to Transit',
                'reference_id'     => null,
                'narrative'        => "Cash moved to Cash-in-Transit for bank deposit",
                'cost_center_id'   => $branchId,
                'officer_id'       => $officerId,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $citAccount->Id,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narrative'  => "Cash-in-Transit (CIT) staging",
                ],
                [
                    'account_id' => $cashAccount->Id,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narrative'  => "Cash on hand transferred to CIT",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Cash-to-transit GL entry posted', [
                'journal_number' => $journal->journal_number,
                'amount'         => $amount,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post cash-to-transit GL entry', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * MOP §6 Step 3 — Cash Deposit to Bank (CIT → Bank)
     * Confirms the bank deposit — clears the CIT holding account.
     *
     *   DR GL:10000/bank_sub_code (Bank account)
     *   CR GL:10100/10130         (Bank Deposits in Transit)
     *
     * @param  float   $amount
     * @param  string  $bankSubCode   10030 = Stanbic, 10020 = ABSA (default Stanbic)
     * @param  int|null $branchId
     * @param  int|null $officerId
     */
    public function postCashDepositEntry(float $amount, string $bankSubCode = '10030', ?int $branchId = null, ?int $officerId = null)
    {
        try {
            if ($amount <= 0) {
                return null;
            }

            $bankAccount = SystemAccount::where('code', '10000')
                ->where('sub_code', $bankSubCode)
                ->first()
                ?? SystemAccount::where('code', '10000')->where('sub_code', '10030')->first();

            $bankTransitAccount = SystemAccount::where('code', '10100')
                ->where('sub_code', '10130')
                ->first()
                ?? SystemAccount::where('code', '10100')->whereNull('sub_code')->first();

            if (!$bankAccount || !$bankTransitAccount) {
                throw new \Exception("Cash deposit accounts not found (bank: {$bankSubCode}, transit: 10130)");
            }

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'Cash Deposit',
                'reference_id'     => null,
                'narrative'        => "Cash deposited to bank — {$bankAccount->name}",
                'cost_center_id'   => $branchId,
                'officer_id'       => $officerId,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $bankAccount->Id,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narrative'  => "Bank account credited with deposit",
                ],
                [
                    'account_id' => $bankTransitAccount->Id,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narrative'  => "Bank deposits-in-transit cleared",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Cash deposit GL entry posted', [
                'journal_number' => $journal->journal_number,
                'amount'         => $amount,
                'bank'           => $bankAccount->name,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post cash deposit GL entry', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * MOP §6 Step 4 — Cash Variance Handling (ZCL)
     * Record a cash shortage or cash overage discovered during reconciliation.
     *
     * Shortage:
     *   DR GL:56300 (Cash Shortage Expense)
     *   CR GL:10000 (Cash on Hand)
     *
     * Over:
     *   DR GL:10000 (Cash on Hand)
     *   CR GL:42000/42060 (Cash Over Income)
     *
     * @param  float   $amount
     * @param  string  $type      'shortage' or 'over'
     * @param  int|null $branchId
     * @param  int|null $officerId
     */
    public function postCashVarianceEntry(float $amount, string $type, ?int $branchId = null, ?int $officerId = null)
    {
        try {
            if ($amount <= 0) {
                return null;
            }

            $cashAccount = SystemAccount::where('code', '10000')
                ->whereNull('sub_code')
                ->first();

            if ($type === 'shortage') {
                $varianceAccount = SystemAccount::where('code', '56300')
                    ->whereNull('sub_code')
                    ->first();
                $narrative = "Cash shortage recorded — ZCL";
            } else {
                $varianceAccount = SystemAccount::where('code', '42000')
                    ->where('sub_code', '42060')
                    ->first();
                $narrative = "Cash over recorded — ZCL";
            }

            if (!$cashAccount || !$varianceAccount) {
                throw new \Exception("Cash variance accounts not found (type: {$type})");
            }

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'Cash Variance',
                'reference_id'     => null,
                'narrative'        => $narrative,
                'cost_center_id'   => $branchId,
                'officer_id'       => $officerId,
                'posted_by'        => auth()->id() ?? 1,
            ];

            if ($type === 'shortage') {
                $journalLines = [
                    [
                        'account_id' => $varianceAccount->Id,
                        'debit'      => $amount,
                        'credit'     => 0,
                        'narrative'  => "Cash shortage expense (ZCL)",
                    ],
                    [
                        'account_id' => $cashAccount->Id,
                        'debit'      => 0,
                        'credit'     => $amount,
                        'narrative'  => "Cash on hand reduced — shortage",
                    ],
                ];
            } else {
                $journalLines = [
                    [
                        'account_id' => $cashAccount->Id,
                        'debit'      => $amount,
                        'credit'     => 0,
                        'narrative'  => "Cash on hand increased — over",
                    ],
                    [
                        'account_id' => $varianceAccount->Id,
                        'debit'      => 0,
                        'credit'     => $amount,
                        'narrative'  => "Cash over income (ZCL)",
                    ],
                ];
            }

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Cash variance GL entry posted', [
                'journal_number' => $journal->journal_number,
                'type'           => $type,
                'amount'         => $amount,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post cash variance GL entry', [
                'type'  => $type,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 7 — OPERATING EXPENSES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * MOP §7 — Operating Expense
     * Record any operational expenditure paid from bank.
     *
     *   DR GL:expense_code/expense_sub_code (Expense account)
     *   CR GL:10000/bank_sub_code           (Bank account)
     *
     * @param  float       $amount
     * @param  string      $expenseCode       e.g. '52000', '53000', '55000'
     * @param  string|null $expenseSubCode    e.g. '52010', '55010'
     * @param  string      $description       Narrative for the expense
     * @param  string|null $date              Y-m-d, defaults to today
     * @param  string      $bankSubCode       Bank account sub-code, default Stanbic '10030'
     * @param  int|null    $branchId
     * @param  int|null    $officerId
     */
    public function postOperatingExpenseEntry(
        float   $amount,
        string  $expenseCode,
        ?string $expenseSubCode = null,
        string  $description    = 'Operating expense',
        ?string $date           = null,
        string  $bankSubCode    = '10030',
        ?int    $branchId       = null,
        ?int    $officerId      = null
    ) {
        try {
            if ($amount <= 0) {
                return null;
            }

            $expenseAccount = $expenseSubCode
                ? SystemAccount::where('code', $expenseCode)->where('sub_code', $expenseSubCode)->first()
                : SystemAccount::where('code', $expenseCode)->whereNull('sub_code')->first();

            if (!$expenseAccount) {
                throw new \Exception("Expense account not found ({$expenseCode}/{$expenseSubCode})");
            }

            $bankAccount = SystemAccount::where('code', '10000')
                ->where('sub_code', $bankSubCode)
                ->first()
                ?? SystemAccount::where('code', '10000')->where('sub_code', '10030')->first();

            if (!$bankAccount) {
                throw new \Exception("Bank account not found for expense payment");
            }

            $transactionDate = $date ? Carbon::parse($date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');

            $journalData = [
                'transaction_date' => $transactionDate,
                'reference_type'   => 'Operating Expense',
                'reference_id'     => null,
                'narrative'        => $description,
                'cost_center_id'   => $branchId,
                'officer_id'       => $officerId,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $expenseAccount->Id,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narrative'  => $description,
                ],
                [
                    'account_id' => $bankAccount->Id,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narrative'  => "Bank payment — {$description}",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Operating expense GL entry posted', [
                'journal_number'  => $journal->journal_number,
                'amount'          => $amount,
                'expense_account' => $expenseAccount->code . '/' . $expenseSubCode,
                'description'     => $description,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post operating expense GL entry', [
                'error'       => $e->getMessage(),
                'description' => $description,
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SECTION 8 — REMUNERATION
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * MOP §8 — Remuneration Accrual
     * Record salary expense at period end (earned but not yet paid).
     *
     *   DR GL:51000/51010 (Salaries and Wages)
     *   CR GL:22000/22010 (Salaries Payable)
     *
     * @param  float   $amount     Total gross payroll for the period
     * @param  string  $period     Human-readable period label, e.g. "March 2026"
     * @param  int|null $branchId
     */
    public function postRemunerationAccrualEntry(float $amount, string $period, ?int $branchId = null)
    {
        try {
            if ($amount <= 0) {
                return null;
            }

            $salaryExpenseAccount = SystemAccount::where('code', '51000')
                ->where('sub_code', '51010')
                ->first()
                ?? SystemAccount::where('code', '51000')->whereNull('sub_code')->first();

            $salariesPayableAccount = SystemAccount::where('code', '22000')
                ->where('sub_code', '22010')
                ->first()
                ?? SystemAccount::where('code', '22000')->whereNull('sub_code')->first();

            if (!$salaryExpenseAccount || !$salariesPayableAccount) {
                throw new \Exception("Remuneration accounts not found (51010 or 22010)");
            }

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'Salary Accrual',
                'reference_id'     => null,
                'narrative'        => "Salary accrual — {$period}",
                'cost_center_id'   => $branchId,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $salaryExpenseAccount->Id,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narrative'  => "Salary expense accrued — {$period}",
                ],
                [
                    'account_id' => $salariesPayableAccount->Id,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narrative'  => "Salaries payable — {$period}",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Remuneration accrual GL entry posted', [
                'journal_number' => $journal->journal_number,
                'amount'         => $amount,
                'period'         => $period,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post remuneration accrual GL entry', [
                'error'  => $e->getMessage(),
                'period' => $period,
            ]);
            return null;
        }
    }

    /**
     * MOP §8 — Remuneration Payment
     * Disburse salaries from bank, clearing the payable liability.
     *
     *   DR GL:22000/22010 (Salaries Payable)
     *   CR GL:10000/bank_sub_code (Bank account)
     *
     * @param  float   $amount
     * @param  string  $period        Human-readable period label, e.g. "March 2026"
     * @param  string  $bankSubCode   10030 = Stanbic (default), 10020 = ABSA
     * @param  int|null $branchId
     */
    public function postRemunerationPaymentEntry(float $amount, string $period, string $bankSubCode = '10030', ?int $branchId = null)
    {
        try {
            if ($amount <= 0) {
                return null;
            }

            $salariesPayableAccount = SystemAccount::where('code', '22000')
                ->where('sub_code', '22010')
                ->first()
                ?? SystemAccount::where('code', '22000')->whereNull('sub_code')->first();

            $bankAccount = SystemAccount::where('code', '10000')
                ->where('sub_code', $bankSubCode)
                ->first()
                ?? SystemAccount::where('code', '10000')->where('sub_code', '10030')->first();

            if (!$salariesPayableAccount || !$bankAccount) {
                throw new \Exception("Remuneration payment accounts not found (22010 or bank: {$bankSubCode})");
            }

            $journalData = [
                'transaction_date' => Carbon::today()->format('Y-m-d'),
                'reference_type'   => 'Salary Payment',
                'reference_id'     => null,
                'narrative'        => "Salary payment — {$period}",
                'cost_center_id'   => $branchId,
                'posted_by'        => auth()->id() ?? 1,
            ];

            $journalLines = [
                [
                    'account_id' => $salariesPayableAccount->Id,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narrative'  => "Salaries payable settled — {$period}",
                ],
                [
                    'account_id' => $bankAccount->Id,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narrative'  => "Bank payment — salaries {$period}",
                ],
            ];

            $journal = JournalEntry::postJournal($journalData, $journalLines);

            Log::info('Remuneration payment GL entry posted', [
                'journal_number' => $journal->journal_number,
                'amount'         => $amount,
                'period'         => $period,
                'bank'           => $bankAccount->name,
            ]);

            return $journal;

        } catch (\Exception $e) {
            Log::error('Failed to post remuneration payment GL entry', [
                'error'  => $e->getMessage(),
                'period' => $period,
            ]);
            return null;
        }
    }
}
