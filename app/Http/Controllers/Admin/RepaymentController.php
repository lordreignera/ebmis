<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Repayment;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\GroupLoanSchedule;
use App\Models\Loan;
use App\Models\LoanFollowUp;
use App\Models\BulkSms;
use App\Models\CashSecurity;
use App\Models\LoanCollateralDocument;
use App\Models\SmsLog;
use App\Models\Member;
use App\Models\Branch;
use App\Models\LoanSchedule;
use App\Models\Product;
use App\Models\User;
use App\Models\RawPayment;
use App\Models\Fee;
use App\Services\AccountingService;
use App\Services\FileStorageService;
use App\Services\LoanAccessService;
use App\Services\MobileMoneyService;
use App\Services\RepaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * RepaymentController - Handles loan repayment processing and schedule calculations
 * 
 * ============================================================================
 * CRITICAL BUSINESS RULES - DO NOT MODIFY WITHOUT UNDERSTANDING
 * ============================================================================
 * 
 * 1. PAYMENT ALLOCATION ORDER (BIMSADMIN Waterfall Method):
 *    Late Fees → Interest → Principal
 *    - Payment is allocated in this order ONLY if amount proves each component was paid
 *    - If payment ≤ P+I, NO late fees are allocated (they remain unpaid)
 *    - If payment > P+I, excess goes to late fees
 * 
 * 2. LATE FEE CALCULATION:
 *    Formula: (Principal + Interest) × 6% × Periods Overdue
 *    Period Types:
 *    - Weekly (1): days_overdue ÷ 7 (rounded up)
 *    - Monthly (2): days_overdue ÷ 30 (rounded up)
 *    - Daily (3): days_overdue (exact days)
 * 
 * 3. LATE FEE FREEZE LOGIC:
 *    - Late fees STOP accumulating when total_balance (P+I+Late Fees - Paid) = 0
 *    - DO NOT use schedule.status field - it may be incorrectly set
 *    - Always calculate actual balance: (P + I + Late Fees) - Total Paid
 *    - Freeze date = last payment date or date_cleared
 * 
 * 4. DATE PARSING:
 *    - Database stores dates in DD-MM-YYYY format (e.g., "26-12-2025")
 *    - strtotime() INCORRECTLY interprets this as MM-DD-YYYY
 *    - Always use parsePaymentDate() helper which handles DD-MM-YYYY correctly
 * 
 * 5. BALANCE CALCULATION:
 *    total_balance = (Principal + Interest + Late Fees - Waivers) - Total Paid
 *    - Use schedule.paid column which includes carry-overs and direct payments
 *    - For receipts: Use repayment.amount for specific payment breakdown
 * 
 * 6. WAIVERS:
 *    - Admin-approved late fee waivers stored in late_fees table (status = 2)
 *    - Deducted from gross late fee to get net late fee (what client owes)
 *    - Waivers reduce amount owed but don't change periods in arrears
 * 
 * ============================================================================
 * KEY METHODS (All must use consistent logic):
 * ============================================================================
 * - store(): Main repayment processing with allocation and balance updates
 * - receipt(): Receipt generation - must match store() allocation logic
 * - calculateLateFee(): Centralized late fee calculation with waiver support
 * - parsePaymentDate(): Handles DD-MM-YYYY date format correctly
 * - checkLoanClosure(): Automatic loan closure when all schedules paid
 * ============================================================================
 */
class RepaymentController extends Controller
{
    protected $mobileMoneyService;
    protected $repaymentService;
    protected $accountingService;
    protected $loanAccessService;

    public function __construct(
        MobileMoneyService $mobileMoneyService,
        RepaymentService $repaymentService,
        AccountingService $accountingService,
        LoanAccessService $loanAccessService
    ) {
        $this->mobileMoneyService  = $mobileMoneyService;
        $this->repaymentService    = $repaymentService;
        $this->accountingService   = $accountingService;
        $this->loanAccessService   = $loanAccessService;
    }

    /**
     * Post a GL journal entry for a confirmed repayment.
     * Non-fatal: logs on failure and returns false so callers can warn the user.
     */
    private function postGLEntry(Repayment $repayment, $loan, array $context = []): bool
    {
        try {
            $journal = $this->accountingService->postRepaymentEntry($repayment, $loan);
            if ($journal) {
                Log::info('GL entry posted for repayment', array_merge([
                    'repayment_id'   => $repayment->id,
                    'journal_number' => $journal->journal_number,
                ], $context));
                return true;
            }
            Log::warning('GL entry not posted for repayment', array_merge(['repayment_id' => $repayment->id], $context));
            return false;
        } catch (\Exception $e) {
            Log::error('GL posting failed but repayment will continue', array_merge([
                'repayment_id' => $repayment->id,
                'gl_error'     => $e->getMessage(),
            ], $context));
            return false;
        }
    }

    /**
     * Get actual disbursement date for a loan
     * Centralized method to avoid code duplication
     * 
     * @param object $loan - Loan model with disbursements relationship loaded
     * @return string|null - Disbursement date or null
     */
    protected function getDisbursementDate($loan)
    {
        // Prioritize actual disbursement record from disbursements table
        if (isset($loan->disbursements) && $loan->disbursements->isNotEmpty()) {
            return $loan->disbursements->first()->created_at;
        }
        
        // Fallback for old loans without disbursement records
        return $loan->datecreated ?? $loan->created_at ?? null;
    }

    /**
     * Calculate late fee at a specific payment date (for KPI allocation)
     */
    protected function calculateLateFeeAtDate($schedule, $loan, $paymentDate)
    {
        $now = $paymentDate ? strtotime($paymentDate) : time();

        $your_date = $this->parsePaymentDate($schedule->payment_date);
        $d = max(0, floor(($now - $your_date) / 86400));

        $dd = 0;
        if ($d > 0) {
            $period_type = $loan->product ? $loan->product->period_type : '1';
            $dd = $period_type == '1' ? ceil($d / 7) : ($period_type == '2' ? ceil($d / 30) : $d);
        }

        $latepaymentOriginal = (($schedule->principal + $schedule->interest) * 0.06) * $dd;

        $totalWaivedAmount = DB::table('late_fees')
            ->where('schedule_id', $schedule->id)
            ->where('status', 2)
            ->sum('amount');

        $latepayment = max(0, $latepaymentOriginal - $totalWaivedAmount);

        return [
            'gross' => $latepaymentOriginal,
            'net' => $latepayment,
            'waived' => $totalWaivedAmount,
            'days_overdue' => $d,
            'periods_overdue' => $dd
        ];
    }

    /**
     * Calculate KPI totals (principal/interest/penalties/fees) from repayments
     */
    protected function calculateRepaymentKpiTotals($repayments, float $feesTotal = 0.0)
    {
        $totals = [
            'total_amount' => 0,
            'total_principal' => 0,
            'total_interest' => 0,
            'total_penalty' => 0,
            'total_fees' => $feesTotal,
            'record_count' => 0,
            'average_payment' => 0,
        ];

        $state = [];

        foreach ($repayments as $repayment) {
            if ((float) $repayment->amount <= 0) {
                continue;
            }

            $totals['total_amount'] += (float) $repayment->amount;
            $totals['record_count']++;

            if (!$repayment->schedule || !$repayment->loan) {
                continue;
            }

            $schedule = $repayment->schedule;
            $loan = $repayment->loan;
            $scheduleId = $schedule->id;

            if (!isset($state[$scheduleId])) {
                $state[$scheduleId] = [
                    'remaining_interest' => (float) $schedule->interest,
                    'remaining_principal' => (float) $schedule->principal,
                    'late_fees_paid' => 0.0,
                ];
            }

            $repaymentDate = $repayment->date_created ?? $repayment->created_at ?? $repayment->datecreated ?? null;
            $lateFeeData = $this->calculateLateFeeAtDate($schedule, $loan, $repaymentDate);
            $remainingLateFees = max(0, (float) $lateFeeData['net'] - $state[$scheduleId]['late_fees_paid']);

            $remainingPI = $state[$scheduleId]['remaining_interest'] + $state[$scheduleId]['remaining_principal'];
            $paymentAmount = (float) $repayment->amount;

            if ($paymentAmount > $remainingPI) {
                $lateFeesPaid = min($paymentAmount - $remainingPI, $remainingLateFees);
                $amountForPI = $paymentAmount - $lateFeesPaid;
            } else {
                $lateFeesPaid = 0;
                $amountForPI = $paymentAmount;
            }

            $interestPaid = min($amountForPI, $state[$scheduleId]['remaining_interest']);
            $principalPaid = min($amountForPI - $interestPaid, $state[$scheduleId]['remaining_principal']);

            $state[$scheduleId]['remaining_interest'] -= $interestPaid;
            $state[$scheduleId]['remaining_principal'] -= $principalPaid;
            $state[$scheduleId]['late_fees_paid'] += $lateFeesPaid;

            $totals['total_interest'] += $interestPaid;
            $totals['total_principal'] += $principalPaid;
            $totals['total_penalty'] += $lateFeesPaid;
        }

        $totals['average_payment'] = $totals['record_count'] > 0
            ? $totals['total_amount'] / $totals['record_count']
            : 0;

        return $totals;
    }

    /**
     * Calculate repayment KPI totals with SQL aggregates so the index page does not
     * load every matching repayment into memory before pagination.
     */
    protected function calculateRepaymentKpiTotalsFast(Request $request, ?Carbon $kpiStart, ?Carbon $kpiEnd, float $feesTotal = 0.0): array
    {
        $query = DB::table('repayments as r')
            ->leftJoin('loan_schedules as s', 's.id', '=', 'r.schedule_id')
            ->leftJoin('personal_loans as l', 'l.id', '=', 'r.loan_id')
            ->leftJoin('members as m', 'm.id', '=', 'l.member_id')
            ->where(function ($q) {
                $q->where('r.status', 1)
                    ->orWhere('r.payment_status', 'Completed');
            })
            ->where('r.amount', '>', 0);

        if ($kpiStart && $kpiEnd) {
            $query->whereBetween('r.date_created', [$kpiStart, $kpiEnd]);
        } else {
            if ($request->filled('start_date')) {
                $query->whereDate('r.date_created', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('r.date_created', '<=', $request->end_date);
            }
        }

        if ($request->filled('method')) {
            $query->where('r.type', $request->method);
        }

        if ($request->filled('branch_id')) {
            $query->where('l.branch_id', $request->branch_id);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('l.code', 'like', "%{$search}%")
                    ->orWhere('m.fname', 'like', "%{$search}%")
                    ->orWhere('m.lname', 'like', "%{$search}%")
                    ->orWhere('m.code', 'like', "%{$search}%")
                    ->orWhere('m.contact', 'like', "%{$search}%")
                    ->orWhere('r.transaction_reference', 'like', "%{$search}%")
                    ->orWhere('r.txn_id', 'like', "%{$search}%");
            });
        }

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as record_count, COALESCE(SUM(r.amount), 0) as total_amount')
            ->first();

        $scheduleRows = (clone $query)
            ->whereNotNull('r.schedule_id')
            ->where('r.schedule_id', '>', 0)
            ->groupBy('r.schedule_id', 's.interest', 's.principal')
            ->selectRaw('
                r.schedule_id,
                COALESCE(s.interest, 0) as schedule_interest,
                COALESCE(s.principal, 0) as schedule_principal,
                COALESCE(SUM(r.amount), 0) as paid_amount
            ')
            ->get();

        $totalInterest = 0.0;
        $totalPrincipal = 0.0;
        $totalPenalty = 0.0;

        foreach ($scheduleRows as $row) {
            $paidAmount = (float) $row->paid_amount;
            $interestPaid = min($paidAmount, (float) $row->schedule_interest);
            $principalPaid = min(max(0, $paidAmount - $interestPaid), (float) $row->schedule_principal);
            $penaltyPaid = max(0, $paidAmount - $interestPaid - $principalPaid);

            $totalInterest += $interestPaid;
            $totalPrincipal += $principalPaid;
            $totalPenalty += $penaltyPaid;
        }

        $recordCount = (int) ($summary->record_count ?? 0);
        $totalAmount = (float) ($summary->total_amount ?? 0);

        return [
            'total_amount' => $totalAmount,
            'total_principal' => $totalPrincipal,
            'total_interest' => $totalInterest,
            'total_penalty' => $totalPenalty,
            'total_fees' => $feesTotal,
            'record_count' => $recordCount,
            'average_payment' => $recordCount > 0 ? $totalAmount / $recordCount : 0,
        ];
    }

    /**
     * Check if all earlier schedules (by date) are fully paid
     * 
     * @param int $scheduleId - Current schedule ID to check
     * @param int $loanId - Loan ID
     * @return array ['allowed' => bool, 'message' => string, 'unpaid_schedule' => object|null]
     */
    protected function checkEarlierSchedulesPaid($scheduleId, $loanId)
    {
        $currentSchedule = LoanSchedule::find($scheduleId);
        
        if (!$currentSchedule) {
            return [
                'allowed' => false,
                'message' => 'Schedule not found',
                'unpaid_schedule' => null
            ];
        }
        
        // IMPORTANT: payment_date is stored as text and may be DD-MM-YYYY.
        // Do NOT compare it in SQL as a string; parse and compare as timestamps.
        $currentDueTs = $this->parsePaymentDate($currentSchedule->payment_date);

        $candidateSchedules = LoanSchedule::where('loan_id', $loanId)
            ->where('id', '!=', $scheduleId)
            ->get()
            ->filter(function ($schedule) use ($currentDueTs) {
                $scheduleDueTs = $this->parsePaymentDate($schedule->payment_date);
                return $scheduleDueTs < $currentDueTs;
            })
            ->sortBy(function ($schedule) {
                return $this->parsePaymentDate($schedule->payment_date);
            });

        $candidateIds = $candidateSchedules->pluck('id')->values()->all();
        $actualPaidMap = [];
        if (!empty($candidateIds)) {
            $actualPaidMap = DB::table('repayments')
                ->whereIn('schedule_id', $candidateIds)
                ->where('amount', '>', 0)
                ->whereNotIn('status', [-1, 2]) // Exclude INVALID/FAILED records
                ->where(function ($query) {
                    $query->where('status', 1)
                        ->orWhere('payment_status', 'Completed');
                })
                ->groupBy('schedule_id')
                ->select('schedule_id', DB::raw('SUM(amount) as total_paid'))
                ->pluck('total_paid', 'schedule_id')
                ->toArray();
        }

        $earlierUnpaidSchedule = $candidateSchedules
            ->first(function ($schedule) use ($actualPaidMap) {
                $requiredAmount = (float) ($schedule->payment ?? ($schedule->principal + $schedule->interest));
                $paidAmount = (float) ($actualPaidMap[$schedule->id] ?? 0);
                $remainingAmount = $requiredAmount - $paidAmount;
                return $remainingAmount > 1; // rounding tolerance
            });
        
        if ($earlierUnpaidSchedule) {
            return [
                'allowed' => false,
                'message' => sprintf(
                    'Cannot pay this schedule. Please pay the earlier schedule due on %s first. Remaining balance: UGX %s',
                    date('d-M-Y', $this->parsePaymentDate($earlierUnpaidSchedule->payment_date)),
                    number_format(
                        max(
                            0,
                            ((float) ($earlierUnpaidSchedule->payment ?? ($earlierUnpaidSchedule->principal + $earlierUnpaidSchedule->interest)))
                            - (float) ($actualPaidMap[$earlierUnpaidSchedule->id] ?? 0)
                        ),
                        0
                    )
                ),
                'unpaid_schedule' => $earlierUnpaidSchedule
            ];
        }
        
        return [
            'allowed' => true,
            'message' => 'Payment allowed',
            'unpaid_schedule' => null
        ];
    }

    /**
     * Parse payment dates that may be in DD-MM-YYYY format
     * 
     * CRITICAL: strtotime() interprets "26-12-2025" as MM-DD-YYYY (wrong!)
     * This helper detects DD-MM-YYYY format and converts correctly
     * 
     * @param string $dateString - Date in various formats
     * @return int Unix timestamp
     * 
     * EXAMPLES:
     * - "26-12-2025" → December 26, 2025 (NOT January 26, 2026)
     * - "2025-12-26" → Works correctly with strtotime()
     * - "Dec 26, 2025" → Works correctly with strtotime()
     */
    protected function parsePaymentDate($dateString)
    {
        if ($dateString instanceof \DateTimeInterface) {
            return $dateString->getTimestamp();
        }

        $dateString = (string) $dateString;

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
     * Confirmed schedule payments keyed by schedule_id.
     */
    protected function getConfirmedSchedulePayments(array $scheduleIds, string $table = 'repayments'): array
    {
        $scheduleIds = array_values(array_unique(array_filter($scheduleIds)));

        if (empty($scheduleIds)) {
            return [];
        }

        $query = DB::table($table)
            ->whereIn('schedule_id', $scheduleIds)
            ->where('amount', '>', 0);

        if ($table === 'repayments') {
            $query->whereNotIn('status', [-1, 2])
                ->where(function ($query) {
                    $query->where('status', 1)
                        ->orWhere('payment_status', 'Completed');
                });
        }

        return $query
            ->groupBy('schedule_id')
            ->select('schedule_id', DB::raw('SUM(amount) as total_paid'))
            ->pluck('total_paid', 'schedule_id')
            ->map(function ($amount) {
                return (float) $amount;
            })
            ->toArray();
    }

    /**
     * Remaining installment balance using principal + interest + net late fees - valid payments.
     */
    protected function getScheduleInstallmentOutstanding($schedule, $loan, array $paidBySchedule, ?array $waivedBySchedule = null): array
    {
        $principal = (float) ($schedule->principal ?? 0);
        $interest = (float) ($schedule->interest ?? 0);
        $lateFeeData = $waivedBySchedule !== null
            ? $this->calculateLateFeeWithWaiverAmount($schedule, $loan, (float) ($waivedBySchedule[$schedule->id] ?? 0))
            : $this->repaymentService->calculateLateFee($schedule, $loan);
        $lateFees = (float) ($lateFeeData['net'] ?? 0);
        $paidFromRepayments = (float) ($paidBySchedule[$schedule->id] ?? 0);
        $paid = $paidFromRepayments;

        $interestPaid = min($interest, $paid);
        $principalPaid = min($principal, max(0, $paid - $interestPaid));
        $lateFeesPaid = min($lateFees, max(0, $paid - $interest - $principal));
        $outstandingInterest = max(0, $interest - $interestPaid);
        $outstandingPrincipal = max(0, $principal - $principalPaid);
        $outstandingLateFees = max(0, $lateFees - $lateFeesPaid);

        return [
            'principal' => $outstandingPrincipal,
            'interest' => $outstandingInterest,
            'late_fees' => $outstandingLateFees,
            'total' => $outstandingPrincipal + $outstandingInterest + $outstandingLateFees,
        ];
    }

    /**
     * Sum unpaid schedule balances for a loan.
     */
    protected function getLoanInstallmentOutstanding($loan, array $paidBySchedule, ?array $waivedBySchedule = null): array
    {
        $totals = [
            'principal' => 0.0,
            'interest' => 0.0,
            'late_fees' => 0.0,
            'total' => 0.0,
        ];

        foreach (($loan->schedules ?? collect()) as $schedule) {
            $components = $this->getScheduleInstallmentOutstanding($schedule, $loan, $paidBySchedule, $waivedBySchedule);
            $totals['principal'] += $components['principal'];
            $totals['interest'] += $components['interest'];
            $totals['late_fees'] = ($totals['late_fees'] ?? 0) + $components['late_fees'];
            $totals['total'] += $components['total'];
        }

        return $totals;
    }

    protected function calculateLateFeeWithWaiverAmount($schedule, $loan, float $waivedAmount): array
    {
        $now = $schedule->date_cleared ? strtotime($schedule->date_cleared) : time();
        $dueTimestamp = $this->parsePaymentDate($schedule->payment_date);
        $daysOverdue = max(0, floor(($now - $dueTimestamp) / 86400));

        $periodsOverdue = 0;
        if ($daysOverdue > 0) {
            $periodType = $loan->product ? $loan->product->period_type : '1';
            $periodsOverdue = $periodType == '1'
                ? ceil($daysOverdue / 7)
                : ($periodType == '2' ? ceil($daysOverdue / 30) : $daysOverdue);
        }

        $gross = (((float) $schedule->principal + (float) $schedule->interest) * 0.06) * $periodsOverdue;

        return [
            'gross' => $gross,
            'net' => max(0, $gross - $waivedAmount),
            'waived' => $waivedAmount,
            'days_overdue' => $daysOverdue,
            'periods_overdue' => $periodsOverdue,
        ];
    }

    /**
     * First unpaid schedule that still has principal/interest remaining.
     */
    protected function getNextOutstandingSchedule($loan, array $paidBySchedule, ?array $waivedBySchedule = null): ?array
    {
        $schedules = ($loan->schedules ?? collect())
            ->sortBy(function ($schedule) {
                $timestamp = $this->parsePaymentDate($schedule->payment_date);
                return $timestamp ?: PHP_INT_MAX;
            });

        foreach ($schedules as $schedule) {
            $components = $this->getScheduleInstallmentOutstanding($schedule, $loan, $paidBySchedule, $waivedBySchedule);

            if ($components['total'] <= 0) {
                continue;
            }

            $dueTimestamp = $this->parsePaymentDate($schedule->payment_date);
            $todayTimestamp = strtotime(date('Y-m-d') . ' 00:00:00');
            $dueDateTimestamp = $dueTimestamp ? strtotime(date('Y-m-d', $dueTimestamp) . ' 00:00:00') : null;

            return [
                'payment_date' => $schedule->payment_date,
                'amount' => $components['total'],
                'days_overdue' => $dueDateTimestamp && $dueDateTimestamp < $todayTimestamp
                    ? (int) floor(($todayTimestamp - $dueDateTimestamp) / 86400)
                    : 0,
            ];
        }

        return null;
    }

    /**
     * Risk profile used on the active-loans follow-up list.
     */
    protected function getActiveLoanRiskProfile($loan): array
    {
        $maxDays = 0;
        $overdueInstallments = 0;
        $todayTimestamp = strtotime(date('Y-m-d') . ' 00:00:00');

        foreach (($loan->schedules ?? collect()) as $schedule) {
            if ((int) ($schedule->status ?? 0) === 1 || !$schedule->payment_date) {
                continue;
            }

            $dueTimestamp = $this->parsePaymentDate($schedule->payment_date);

            if (!$dueTimestamp) {
                continue;
            }

            $dueDateTimestamp = strtotime(date('Y-m-d', $dueTimestamp) . ' 00:00:00');

            if ($dueDateTimestamp >= $todayTimestamp) {
                continue;
            }

            $days = (int) floor(($todayTimestamp - $dueDateTimestamp) / 86400);
            $maxDays = max($maxDays, $days);
            $overdueInstallments++;
        }

        $daysClass = $this->getRiskClassByDays($maxDays);
        $installmentsClass = $this->getRiskClassByInstallments($overdueInstallments);
        $classification = $this->getWorseRiskClass($daysClass, $installmentsClass);

        return [
            'classification' => $classification,
            'dpd' => $maxDays,
            'overdue_installments' => $overdueInstallments,
            'badge' => match ($classification) {
                'Performing' => 'success',
                'Watch' => 'warning',
                'Substandard' => 'info',
                'Doubtful' => 'danger',
                'Loss' => 'dark',
                default => 'secondary',
            },
            'requires_follow_up' => $classification !== 'Performing',
        ];
    }

    protected function getRiskClassByDays(int $daysOverdue): string
    {
        if ($daysOverdue <= 0) {
            return 'Performing';
        }

        if ($daysOverdue <= 30) {
            return 'Watch';
        }

        if ($daysOverdue <= 90) {
            return 'Substandard';
        }

        if ($daysOverdue <= 180) {
            return 'Doubtful';
        }

        return 'Loss';
    }

    protected function getRiskClassByInstallments(int $overdueInstallments): string
    {
        if ($overdueInstallments <= 0) {
            return 'Performing';
        }

        if ($overdueInstallments > 6) {
            return 'Loss';
        }

        if ($overdueInstallments >= 4) {
            return 'Doubtful';
        }

        if ($overdueInstallments >= 2) {
            return 'Substandard';
        }

        return 'Watch';
    }

    protected function getWorseRiskClass(string $firstClass, string $secondClass): string
    {
        $rank = [
            'Performing' => 0,
            'Watch' => 1,
            'Substandard' => 2,
            'Doubtful' => 3,
            'Loss' => 4,
        ];

        return ($rank[$firstClass] ?? 0) >= ($rank[$secondClass] ?? 0)
            ? $firstClass
            : $secondClass;
    }

    /**
     * Attach latest follow-up metadata without doing per-row queries.
     */
    protected function attachLoanFollowUps($loans): void
    {
        foreach ($loans as $loan) {
            $loan->latest_follow_up = null;
            $loan->follow_up_count = 0;
            $loan->has_follow_up = false;
            $loan->follow_up_due = false;
        }

        if ($loans->isEmpty() || !Schema::hasTable('loan_follow_ups')) {
            return;
        }

        $personalIds = $loans
            ->filter(fn ($loan) => ($loan->loan_type ?? null) === 'personal')
            ->pluck('id')
            ->values()
            ->all();

        $groupIds = $loans
            ->filter(fn ($loan) => ($loan->loan_type ?? null) === 'group')
            ->pluck('id')
            ->values()
            ->all();

        if (empty($personalIds) && empty($groupIds)) {
            return;
        }

        $baseScope = function ($query) use ($personalIds, $groupIds) {
            $query->where(function ($query) use ($personalIds, $groupIds) {
                if (!empty($personalIds)) {
                    $query->orWhere(function ($query) use ($personalIds) {
                        $query->where('loan_type', 'personal')
                            ->whereIn('loan_id', $personalIds);
                    });
                }

                if (!empty($groupIds)) {
                    $query->orWhere(function ($query) use ($groupIds) {
                        $query->where('loan_type', 'group')
                            ->whereIn('loan_id', $groupIds);
                    });
                }
            });
        };

        $counts = LoanFollowUp::query()
            ->where($baseScope)
            ->groupBy('loan_type', 'loan_id')
            ->select('loan_type', 'loan_id', DB::raw('COUNT(*) as total'))
            ->get()
            ->keyBy(fn ($row) => $row->loan_type . ':' . $row->loan_id);

        $latestIds = LoanFollowUp::query()
            ->where($baseScope)
            ->groupBy('loan_type', 'loan_id')
            ->select(DB::raw('MAX(id) as id'))
            ->pluck('id')
            ->filter()
            ->all();

        $latestFollowUps = empty($latestIds)
            ? collect()
            : LoanFollowUp::with('createdBy:id,name')
                ->whereIn('id', $latestIds)
                ->get()
                ->keyBy(fn ($followUp) => $followUp->loan_type . ':' . $followUp->loan_id);

        foreach ($loans as $loan) {
            $key = ($loan->loan_type ?? 'personal') . ':' . $loan->id;
            $latest = $latestFollowUps->get($key);

            $loan->latest_follow_up = $latest;
            $loan->follow_up_count = (int) ($counts->get($key)->total ?? 0);
            $loan->has_follow_up = $loan->follow_up_count > 0;
            $loan->follow_up_due = $latest && $latest->next_follow_up_date
                ? $latest->next_follow_up_date->isPast() || $latest->next_follow_up_date->isToday()
                : false;
        }
    }

    /**
     * Attach loan-level collateral status without per-row queries.
     */
    protected function attachLoanCollateralStatus($loans): void
    {
        foreach ($loans as $loan) {
            $this->applyCollateralStatus($loan, collect());
        }

        if ($loans->isEmpty() || !Schema::hasTable('cash_securities')) {
            return;
        }

        $personalIds = $loans
            ->filter(fn ($loan) => ($loan->loan_type ?? null) === 'personal')
            ->pluck('id')
            ->values()
            ->all();

        if (empty($personalIds)) {
            return;
        }

        $cashSecurities = CashSecurity::query()
            ->whereIn('loan_id', $personalIds)
            ->where('returned', 0)
            ->get()
            ->groupBy('loan_id');
        $documentCounts = $this->getCollateralDocumentCounts($personalIds);

        foreach ($loans as $loan) {
            $linkedCashSecurities = ($loan->loan_type ?? null) === 'personal'
                ? $cashSecurities->get($loan->id, collect())
                : collect();

            $this->applyCollateralStatus($loan, $linkedCashSecurities, $documentCounts[(int) $loan->id] ?? 0);
        }
    }

    protected function applyCollateralStatus($loan, $cashSecurities, int $documentCount = 0): void
    {
        $nonCashTypes = $this->getLoanNonCashCollateralTypes($loan);
        $confirmedCash = (float) $cashSecurities->where('status', 1)->sum('amount');
        $pendingCash = (float) $cashSecurities->where('status', 0)->sum('amount');

        $summaryParts = $nonCashTypes;
        $hasDocumentedNonCash = !empty($nonCashTypes) && $documentCount > 0;

        if (!empty($nonCashTypes)) {
            $summaryParts[] = $documentCount > 0
                ? $documentCount . ' collateral document(s)'
                : 'collateral document missing';
        }

        if ($confirmedCash > 0) {
            $summaryParts[] = 'Cash security UGX ' . number_format($confirmedCash, 0);
        }

        if ($pendingCash > 0) {
            $summaryParts[] = 'Pending cash security UGX ' . number_format($pendingCash, 0);
        }

        $loan->non_cash_collateral_types = $nonCashTypes;
        $loan->collateral_document_count = $documentCount;
        $loan->cash_security_amount = $confirmedCash;
        $loan->pending_cash_security_amount = $pendingCash;
        $loan->has_collateral = $hasDocumentedNonCash || $confirmedCash > 0;
        $loan->collateral_summary = empty($summaryParts) ? 'No collateral recorded' : implode('; ', $summaryParts);
    }

    protected function getCollateralDocumentCounts(array $loanIds): array
    {
        $loanIds = array_values(array_unique(array_filter($loanIds)));
        if (empty($loanIds)) {
            return [];
        }

        $counts = [];

        if (Schema::hasTable('loan_collateral_documents')) {
            $storedCounts = LoanCollateralDocument::query()
                ->where('loan_type', 'personal')
                ->whereIn('loan_id', $loanIds)
                ->groupBy('loan_id')
                ->select('loan_id', DB::raw('COUNT(*) as total'))
                ->pluck('total', 'loan_id')
                ->toArray();

            foreach ($storedCounts as $loanId => $total) {
                $counts[(int) $loanId] = ($counts[(int) $loanId] ?? 0) + (int) $total;
            }
        }

        if (Schema::hasTable('client_loan_applications')) {
            $applicationCounts = DB::table('client_loan_applications')
                ->whereIn('loan_id', $loanIds)
                ->groupBy('loan_id')
                ->select('loan_id', DB::raw("
                    SUM(
                        CASE WHEN collateral_1_doc_photo IS NOT NULL AND collateral_1_doc_photo <> '' THEN 1 ELSE 0 END
                        + CASE WHEN collateral_2_doc_photo IS NOT NULL AND collateral_2_doc_photo <> '' THEN 1 ELSE 0 END
                    ) as total
                "))
                ->pluck('total', 'loan_id')
                ->toArray();

            foreach ($applicationCounts as $loanId => $total) {
                $counts[(int) $loanId] = ($counts[(int) $loanId] ?? 0) + (int) $total;
            }
        }

        return $counts;
    }

    protected function getLoanNonCashCollateralTypes($loan): array
    {
        $fields = [
            'immovable_assets' => 'Immovable',
            'moveable_assets' => 'Moveable',
            'intellectual_property' => 'Intellectual property',
            'stocks_collateral' => 'Stock',
            'livestock_collateral' => 'Livestock',
        ];

        $types = [];

        foreach ($fields as $field => $label) {
            if (trim((string) ($loan->{$field} ?? '')) !== '') {
                $types[] = $label;
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * Contact details for one loan follow-up recipient.
     */
    protected function getFollowUpSmsRecipient($loan, string $loanType): array
    {
        if ($loanType === 'personal') {
            $loan->loadMissing('member');

            return [
                'member_id' => $loan->member_id ?? null,
                'phone' => $loan->member->contact ?? null,
                'name' => trim(($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? '')) ?: ($loan->member->code ?? 'Client'),
            ];
        }

        $loan->loadMissing('group');

        return [
            'member_id' => null,
            'phone' => $loan->group->contact
                ?? $loan->group_representative_phone
                ?? null,
            'name' => $loan->group->name ?? 'Group client',
        ];
    }

    /**
     * Send a follow-up SMS through the existing bulk SMS log flow.
     */
    protected function sendFollowUpSms($loan, string $loanType, string $message): array
    {
        $recipient = $this->getFollowUpSmsRecipient($loan, $loanType);
        $phone = preg_replace('/\s+/', '', (string) ($recipient['phone'] ?? ''));

        if ($phone === '') {
            return [
                'sent' => false,
                'message' => 'SMS not sent: borrower phone number is missing.',
            ];
        }

        $bulkSms = BulkSms::create([
            'title' => 'Loan follow-up: ' . ($loan->code ?? $loan->id),
            'message' => $message,
            'recipient_type' => 'individual',
            'recipient_group' => null,
            'recipients_count' => 1,
            'successful_count' => 1,
            'failed_count' => 0,
            'status' => 'completed',
            'scheduled_at' => now(),
            'completed_at' => now(),
            'sent_by' => auth()->id(),
        ]);

        SmsLog::create([
            'sms_id' => $bulkSms->id,
            'member_id' => $recipient['member_id'],
            'phone' => $phone,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return [
            'sent' => true,
            'message' => 'SMS logged as sent to ' . $phone . '.',
        ];
    }

    /**
     * Store non-cash collateral directly against the active loan record.
     */
    public function storeCollateral(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => ['required', 'integer'],
            'loan_type' => ['required', 'in:personal,group'],
            'collateral_field' => ['required', 'in:immovable_assets,moveable_assets,intellectual_property,stocks_collateral,livestock_collateral'],
            'description' => ['required', 'string', 'min:3', 'max:2000'],
            'estimated_value' => ['required', 'numeric', 'min:1'],
            'forced_sale_value' => ['nullable', 'numeric', 'min:0'],
            'collateral_document' => ['required', 'file', 'max:20480'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $estimatedValue = (float) $request->input('estimated_value', 0);
            $forcedSaleValue = $request->input('forced_sale_value');

            if ($forcedSaleValue !== null && $forcedSaleValue !== '' && (float) $forcedSaleValue > $estimatedValue) {
                $validator->errors()->add('forced_sale_value', 'The forced sale value cannot be greater than the estimated value.');
            }

            $file = $request->file('collateral_document');

            if (!$file || !$file->isValid()) {
                $validator->errors()->add('collateral_document', 'The collateral document could not be uploaded. Please reselect the file and try again.');
                return;
            }

            $realPath = $file->getRealPath();
            if (!$realPath || !is_readable($realPath)) {
                $validator->errors()->add('collateral_document', 'The collateral document could not be read after upload. Please reselect the file and try again.');
                return;
            }

            if (!$this->isAcceptedCollateralDocument($file)) {
                $validator->errors()->add('collateral_document', 'Upload a valid PDF, JPG, JPEG, or PNG collateral document.');
            }
        });

        $validated = $validator->validate();

        $loan = $validated['loan_type'] === 'group'
            ? GroupLoan::find($validated['loan_id'])
            : PersonalLoan::find($validated['loan_id']);

        if (!$loan) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found for collateral capture.',
                ], 404);
            }

            return back()->with('error', 'Loan not found for collateral capture.');
        }

        $this->loanAccessService->ensureLoanAccess($loan);

        $field = $validated['collateral_field'];
        $description = trim($validated['description']);
        $estimatedValue = (float) $validated['estimated_value'];
        $forcedSaleValue = isset($validated['forced_sale_value']) && $validated['forced_sale_value'] !== null
            ? (float) $validated['forced_sale_value']
            : null;
        $current = trim((string) ($loan->{$field} ?? ''));
        $file = $request->file('collateral_document');
        $documentName = $file->getClientOriginalName();
        $documentMimeType = $file->getMimeType();
        $documentSize = $file->getSize();
        $path = FileStorageService::storeFile(
            $file,
            'loan-collateral-documents/' . $validated['loan_type'] . '/' . $loan->id
        );

        $existingLines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $current)));
        $loan->{$field} = in_array($description, $existingLines, true)
            ? $current
            : ($current === '' ? $description : $current . "\n" . $description);

        $loan->save();

        LoanCollateralDocument::create([
            'loan_type' => $validated['loan_type'],
            'loan_id' => $loan->id,
            'member_id' => $loan->member_id ?? null,
            'collateral_field' => $field,
            'document_name' => $documentName,
            'document_type' => 'collateral_evidence',
            'estimated_value' => $estimatedValue,
            'forced_sale_value' => $forcedSaleValue,
            'file_path' => $path,
            'file_type' => $documentMimeType,
            'file_size' => $documentSize,
            'description' => $description,
            'uploaded_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Collateral recorded successfully.',
            ]);
        }

        return back()->with('success', 'Collateral recorded successfully.');
    }

    protected function isAcceptedCollateralDocument($file): bool
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mimeType = strtolower((string) $file->getMimeType());
        $realPath = $file->getRealPath();

        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return in_array($mimeType, ['image/jpeg', 'image/png'], true) || @getimagesize($realPath) !== false;
        }

        if ($extension !== 'pdf') {
            return false;
        }

        $handle = @fopen($realPath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 1024);
        fclose($handle);

        return strpos($header, '%PDF-') !== false;
    }

    /**
     * Return read-only collateral details for the active-loans view.
     */
    public function showCollateral(Request $request, $loanId)
    {
        $loanType = $request->get('loan_type', 'personal');

        if (!in_array($loanType, ['personal', 'group'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid loan type.',
            ], 422);
        }

        $loan = $loanType === 'group'
            ? GroupLoan::with('group')->find($loanId)
            : PersonalLoan::with('member')->find($loanId);

        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found.',
            ], 404);
        }

        $this->loanAccessService->ensureLoanAccess($loan);

        $cashSecurities = Schema::hasTable('cash_securities')
            ? CashSecurity::query()
                ->where('loan_id', $loan->id)
                ->where('returned', 0)
                ->orderByDesc('datecreated')
                ->get()
            : collect();

        $documents = $this->getLoanCollateralDocuments($loan, $loanType);
        $this->applyCollateralStatus($loan, $cashSecurities, $documents->count());

        return response()->json([
            'success' => true,
            'loan' => [
                'id' => $loan->id,
                'code' => $loan->code ?? $loan->id,
                'borrower' => $loanType === 'group'
                    ? ($loan->group->name ?? 'Group loan')
                    : trim(($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? '')),
                'summary' => $loan->collateral_summary,
                'has_collateral' => (bool) $loan->has_collateral,
            ],
            'non_cash' => $this->getLoanNonCashCollateralDetails($loan),
            'cash_securities' => $cashSecurities->map(function ($security) {
                return [
                    'amount' => (float) $security->amount,
                    'amount_formatted' => number_format((float) $security->amount, 0),
                    'payment_type' => $security->payment_type_name ?? 'Unknown',
                    'status' => (int) $security->status === 1 ? 'Paid' : ((int) $security->status === 0 ? 'Pending' : 'Failed'),
                    'reference' => $security->transaction_reference ?: $security->pay_ref,
                    'description' => $security->description ?: 'Cash security deposit',
                    'date' => optional($security->datecreated)->format('Y-m-d H:i'),
                ];
            })->values(),
            'documents' => $documents->values(),
        ]);
    }

    protected function getLoanNonCashCollateralDetails($loan): array
    {
        $fields = [
            'immovable_assets' => 'Immovable assets',
            'moveable_assets' => 'Moveable assets',
            'intellectual_property' => 'Intellectual property',
            'stocks_collateral' => 'Business stock',
            'livestock_collateral' => 'Livestock',
        ];

        $details = [];

        foreach ($fields as $field => $label) {
            $description = trim((string) ($loan->{$field} ?? ''));

            if ($description === '') {
                continue;
            }

            $details[] = [
                'field' => $field,
                'type' => $label,
                'description' => $description,
            ];
        }

        return $details;
    }

    protected function getLoanCollateralDocuments($loan, string $loanType)
    {
        $documents = collect();

        if (Schema::hasTable('loan_collateral_documents')) {
            $documents = LoanCollateralDocument::query()
                ->where('loan_type', $loanType)
                ->where('loan_id', $loan->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($document) {
                    return [
                        'name' => $document->document_name,
                        'type' => $document->document_type ?: 'Collateral evidence',
                        'description' => $document->description,
                        'estimated_value' => $document->estimated_value !== null ? (float) $document->estimated_value : null,
                        'estimated_value_formatted' => $document->estimated_value !== null ? number_format((float) $document->estimated_value, 0) : null,
                        'forced_sale_value' => $document->forced_sale_value !== null ? (float) $document->forced_sale_value : null,
                        'forced_sale_value_formatted' => $document->forced_sale_value !== null ? number_format((float) $document->forced_sale_value, 0) : null,
                        'url' => $document->file_url,
                        'uploaded_at' => optional($document->created_at)->format('Y-m-d H:i'),
                    ];
                });
        }

        if ($loanType === 'personal' && Schema::hasTable('client_loan_applications')) {
            $applicationDocs = DB::table('client_loan_applications')
                ->where('loan_id', $loan->id)
                ->select(
                    'collateral_1_doc_photo',
                    'collateral_2_doc_photo',
                    'collateral_1_description',
                    'collateral_2_description',
                    'collateral_1_client_value',
                    'collateral_2_client_value',
                    'fsv_collateral_1',
                    'fsv_collateral_2'
                )
                ->get()
                ->flatMap(function ($application) {
                    $rows = [];

                    foreach ([1, 2] as $index) {
                        $path = trim((string) ($application->{"collateral_{$index}_doc_photo"} ?? ''));

                        if ($path === '') {
                            continue;
                        }

                        $estimatedValue = (float) ($application->{"collateral_{$index}_client_value"} ?? 0);
                        $forcedSaleValue = (float) ($application->{"fsv_collateral_{$index}"} ?? 0);

                        $rows[] = [
                            'name' => 'Collateral ' . $index . ' application document',
                            'type' => 'Application collateral',
                            'description' => $application->{"collateral_{$index}_description"} ?? null,
                            'estimated_value' => $estimatedValue > 0 ? $estimatedValue : null,
                            'estimated_value_formatted' => $estimatedValue > 0 ? number_format($estimatedValue, 0) : null,
                            'forced_sale_value' => $forcedSaleValue > 0 ? $forcedSaleValue : null,
                            'forced_sale_value_formatted' => $forcedSaleValue > 0 ? number_format($forcedSaleValue, 0) : null,
                            'url' => FileStorageService::getFileUrl($path),
                            'uploaded_at' => null,
                        ];
                    }

                    return $rows;
                });

            $documents = $documents->merge($applicationDocs);
        }

        return $documents;
    }

    /**
     * Display active loans for repayment management
     */
    public function activeLoans(Request $request)
    {
        if ($request->get('type') === 'personal') {
            return $this->activePersonalLoansOptimized($request);
        }

        // Get active loans from both personal and group loans tables
        // Include status 2 (Disbursed) AND status 3 (marked as closed but may have unpaid schedules)
        // We'll filter out truly closed loans later using getActualStatus()
        $personalLoansQuery = $this->loanAccessService->scopeActiveLoanQuery(PersonalLoan::whereIn('status', [2, 3]))
            ->whereHas('schedules', function ($query) {
                $query->where('status', '!=', 1);
            })
            ->with([
                'member:id,fname,lname,contact', 
                'branch:id,name', 
                'product:id,name,period_type',
                'schedules',
                'disbursements' => function($query) {
                    $query->where('status', 2)->orderBy('created_at', 'desc');
                }
            ]);

        $groupLoansQuery = $this->loanAccessService->scopeActiveLoanQuery(GroupLoan::whereIn('status', [2, 3]))
            ->whereHas('schedules', function ($query) {
                $query->where('status', '!=', 1);
            })
            ->with([
                'group:id,name', 
                'branch:id,name', 
                'product:id,name,period_type',
                'schedules',
                'disbursements' => function($query) {
                    $query->where('status', 2)->orderBy('created_at', 'desc');
                }
            ]);

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->get('search');
            $personalLoansQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('member', function($q) use ($search) {
                      $q->where('fname', 'like', "%{$search}%")
                        ->orWhere('lname', 'like', "%{$search}%")
                        ->orWhere('contact', 'like', "%{$search}%");
                  });
            });
            
            $groupLoansQuery->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('group', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply branch filter
        if ($request->filled('branch')) {
            $personalLoansQuery->where('branch_id', $request->get('branch'));
            $groupLoansQuery->where('branch_id', $request->get('branch'));
        }

        // Apply product filter
        if ($request->filled('product')) {
            $personalLoansQuery->where('product_type', $request->get('product'));
            $groupLoansQuery->where('product_type', $request->get('product'));
        }

        // Filter by loan type
        $loanType = $request->get('type');
        
        // When searching, limit results for performance
        $isSearching = $request->filled('search');
        $maxSearchResults = 50; // Limit search results to first 50 matches
        
        if ($loanType === 'personal') {
            if ($isSearching) {
                $allLoans = $personalLoansQuery->orderBy('datecreated', 'desc')->limit($maxSearchResults)->get();
            } else {
                $allLoans = $personalLoansQuery->orderBy('datecreated', 'desc')->get();
            }
        } elseif ($loanType === 'group') {
            if ($isSearching) {
                $allLoans = $groupLoansQuery->orderBy('datecreated', 'desc')->limit($maxSearchResults)->get();
            } else {
                $allLoans = $groupLoansQuery->orderBy('datecreated', 'desc')->get();
            }
        } else {
            // Get both and merge, then sort by date
            if ($isSearching) {
                $personalLoans = $personalLoansQuery->limit($maxSearchResults)->get();
                $groupLoans = $groupLoansQuery->limit($maxSearchResults)->get();
            } else {
                $personalLoans = $personalLoansQuery->get();
                $groupLoans = $groupLoansQuery->get();
            }
            $allLoans = $personalLoans->concat($groupLoans)->sortByDesc('datecreated')->values();
        }

        // Filter out truly closed loans (status = 3 with all schedules paid)
        // Keep ONLY loans that are actually running:
        // - Status 2 (Disbursed) with unpaid schedules = Active loans
        // - Status 3 (marked closed) but with unpaid schedules = Incorrectly closed
        // Exclude: pending, approved, rejected, stopped, restructured, and truly closed
        $allLoans = $allLoans->filter(function($loan) {
            $actualStatus = $loan->getActualStatus();
            $schedules = $loan->schedules ?? collect();
            $unpaidCount = $schedules->where('status', '!=', 1)->count();

            // ONLY include loans that are 'running' (disbursed + unpaid schedules)
            if ($actualStatus !== 'running') {
                return false;
            }
            
            // Double-check: Must have unpaid schedules
            if ($schedules->isEmpty()) {
                return false; // No schedules = not active
            }
            
            return $unpaidCount > 0; // Only include if has unpaid schedules
        })->values();

        $personalScheduleIds = $allLoans
            ->filter(fn ($loan) => $loan instanceof PersonalLoan)
            ->flatMap(fn ($loan) => $loan->schedules->pluck('id'))
            ->all();

        $groupScheduleIds = $allLoans
            ->filter(fn ($loan) => $loan instanceof GroupLoan)
            ->flatMap(fn ($loan) => $loan->schedules->pluck('id'))
            ->all();

        $personalPaidBySchedule = $this->getConfirmedSchedulePayments($personalScheduleIds, 'repayments');
        $groupPaidBySchedule = $this->getConfirmedSchedulePayments($groupScheduleIds, 'group_repayments');
        
        // Detect potential duplicate loans (same member, multiple loans from 2025 onwards)
        $memberLoanCounts = [];
        foreach ($allLoans as $l) {
            if (isset($l->member_id)) {
                $memberId = $l->member_id;
                if (!isset($memberLoanCounts[$memberId])) {
                    $memberLoanCounts[$memberId] = [];
                }
                
                // Check if disbursed in 2025 or later
                $disbursedDate = $l->date_approved ?? $l->datecreated ?? null;
                if ($disbursedDate) {
                    $year = date('Y', strtotime($disbursedDate));
                    if ($year >= 2025) {
                        $memberLoanCounts[$memberId][] = [
                            'loan_id' => $l->id,
                            'date' => $disbursedDate
                        ];
                    }
                }
            }
        }
        
        // Map and calculate loan details
        $loans = $allLoans->map(function($loan) use ($personalPaidBySchedule, $groupPaidBySchedule, $memberLoanCounts) {
            try {
                // Determine loan type and set borrower info
                if (isset($loan->member)) {
                    $loan->loan_type = 'personal';
                    $loan->borrower_name = trim(($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? ''));
                    $loan->phone_number = $loan->member->contact ?? 'N/A';
                    $loan->member_id_value = $loan->member_id;
                } elseif (isset($loan->group)) {
                    $loan->loan_type = 'group';
                    $loan->borrower_name = $loan->group->name ?? 'N/A';
                    $loan->phone_number = 'Group Loan';
                    $loan->member_id_value = null;
                } else {
                    $loan->loan_type = 'unknown';
                    $loan->borrower_name = 'N/A';
                    $loan->phone_number = 'N/A';
                    $loan->member_id_value = null;
                }
            
            $loan->branch_name = $loan->branch->name ?? 'N/A';
            $loan->product_name = $loan->product->name ?? 'N/A';
            $loan->loan_code = $loan->code;
            $loan->principal_amount = (float) $loan->principal;
            
            // Set disbursement date using centralized helper method
            $loan->disbursement_date = $this->getDisbursementDate($loan);
            
            // Check if this loan is a potential duplicate
            $loan->is_potential_duplicate = false;
            if (isset($loan->member_id_value) && isset($memberLoanCounts[$loan->member_id_value])) {
                $memberLoans = $memberLoanCounts[$loan->member_id_value];
                if (count($memberLoans) > 1) {
                    // Multiple loans from 2025 for same member
                    $loan->is_potential_duplicate = true;
                    $loan->duplicate_loans_count = count($memberLoans);
                }
            }
            
            $paidBySchedule = $loan instanceof GroupLoan ? $groupPaidBySchedule : $personalPaidBySchedule;
            $outstanding = $this->getLoanInstallmentOutstanding($loan, $paidBySchedule);

            $loan->outstanding_principal = $outstanding['principal'];
            $loan->outstanding_interest = $outstanding['interest'];
            $loan->outstanding_late_fees = $outstanding['late_fees'];
            $loan->outstanding_balance = $outstanding['total'];

            $risk = $this->getActiveLoanRiskProfile($loan);
            $loan->risk_classification = $risk['classification'];
            $loan->risk_dpd = $risk['dpd'];
            $loan->risk_overdue_installments = $risk['overdue_installments'];
            $loan->risk_badge = $risk['badge'];
            $loan->requires_follow_up = $risk['requires_follow_up'];

            $nextOutstandingSchedule = $this->getNextOutstandingSchedule($loan, $paidBySchedule);

            if ($nextOutstandingSchedule) {
                $loan->next_due_date = $nextOutstandingSchedule['payment_date'];
                $loan->next_due_amount = $nextOutstandingSchedule['amount'];
                $loan->days_overdue = $nextOutstandingSchedule['days_overdue'];
            } else {
                $loan->next_due_date = null;
                $loan->next_due_amount = 0.0;
                $loan->days_overdue = 0;
            }
            
                return $loan;
            } catch (\Exception $e) {
                // Log error but don't break - return loan with default values
                \Log::error("Error processing loan {$loan->id}: " . $e->getMessage());
                $loan->outstanding_balance = 0;
                $loan->outstanding_principal = 0;
                $loan->outstanding_interest = 0;
                $loan->outstanding_late_fees = 0;
                $loan->next_due_date = null;
                $loan->next_due_amount = 0;
                $loan->days_overdue = 0;
                return $loan;
            }
        });

        // Filter loans with outstanding balance (include overpayments as they need refunds)
        $loans = $loans->filter(function($loan) {
            $hasBalance = isset($loan->outstanding_balance) && $loan->outstanding_balance != 0;

            // Status-2 loans that show a P+I balance of 0 may still have outstanding late fees.
            // checkAndCloseLoanIfComplete() uses calculateLateFee() and will NOT set status=3
            // until late fees are also settled. So any loan still in status=2 is genuinely open.
            $isStillActive = isset($loan->status) && $loan->status == 2;

            return $hasBalance || $isStillActive;
        });

        $this->attachLoanFollowUps($loans);
        $this->attachLoanCollateralStatus($loans);

        if ($request->filled('status')) {
            $statusFilter = $request->get('status');
            $loans = $loans->filter(function ($loan) use ($statusFilter) {
                return match ($statusFilter) {
                    'current' => (int) ($loan->risk_dpd ?? 0) === 0,
                    'overdue' => (int) ($loan->risk_dpd ?? 0) > 0,
                    'restructured' => (int) ($loan->restructured ?? 0) === 1,
                    'risk_followup' => (bool) ($loan->requires_follow_up ?? false),
                    'missing_followup' => (bool) ($loan->requires_follow_up ?? false) && !(bool) ($loan->has_follow_up ?? false),
                    'missing_collateral' => !(bool) ($loan->has_collateral ?? false),
                    default => true,
                };
            })->values();
        }

        // Store the full collection for stats calculation
        $allActiveLoansForStats = $loans;

        // Paginate manually
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 25, 50, 100], true) ? $perPage : 20;
        $totalLoans = $loans->count();
        $currentItems = $loans->slice(($currentPage - 1) * $perPage, $perPage)->all();
        
        $loans = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $totalLoans,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Get filter options
        $branches = $this->loanAccessService->branchesForActiveLoanOperations(Branch::active())->orderBy('name')->get();
        $products = Product::loanProducts()->active()->orderBy('name')->get();

        // Calculate stats from the SAME filtered loans we're displaying
        $stats = [
            'total_active' => $totalLoans, // Use the actual count of filtered loans
            'outstanding_amount' => $allActiveLoansForStats->sum('outstanding_balance'), // Use pre-calculated values
            'outstanding_principal' => $allActiveLoansForStats->sum('outstanding_principal'),
            'outstanding_interest' => $allActiveLoansForStats->sum('outstanding_interest'),
            'outstanding_late_fees' => $allActiveLoansForStats->sum('outstanding_late_fees'),
            'overdue_count' => $allActiveLoansForStats->filter(function($loan) {
                return (int) ($loan->days_overdue ?? 0) > 0;
            })->count(),
            'risk_followup_count' => $allActiveLoansForStats->where('requires_follow_up', true)->count(),
            'followed_up_count' => $allActiveLoansForStats->filter(function ($loan) {
                return (bool) ($loan->requires_follow_up ?? false) && (bool) ($loan->has_follow_up ?? false);
            })->count(),
            'missing_followup_count' => $allActiveLoansForStats->filter(function ($loan) {
                return (bool) ($loan->requires_follow_up ?? false) && !(bool) ($loan->has_follow_up ?? false);
            })->count(),
            'followup_due_count' => $allActiveLoansForStats->where('follow_up_due', true)->count(),
            'missing_collateral_count' => $allActiveLoansForStats->filter(function ($loan) {
                return !(bool) ($loan->has_collateral ?? false);
            })->count(),
            'collections_today' => $this->getAccessiblePersonalCollectionsToday(),
        ];

        return view('admin.loans.active', compact('loans', 'branches', 'products', 'stats'));
    }

    /**
     * Fast path for the personal active-loans page: paginate before per-loan
     * schedule/risk calculations so production does not process the whole book.
     */
    protected function activePersonalLoansOptimized(Request $request)
    {
        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 25, 50, 100], true) ? $perPage : 20;

        $baseQuery = $this->loanAccessService->scopeActiveLoanQuery(PersonalLoan::query())
            ->whereIn('status', [2, 3])
            ->whereHas('schedules', function ($query) {
                $query->where('status', '!=', 1);
            });

        if ($request->filled('search')) {
            $search = $request->get('search');
            $baseQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%")
                            ->orWhere('contact', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('branch')) {
            $baseQuery->where('branch_id', $request->get('branch'));
        }

        if ($request->filled('product')) {
            $baseQuery->where('product_type', $request->get('product'));
        }

        $loans = (clone $baseQuery)
            ->with([
                'member:id,fname,lname,contact',
                'branch:id,name',
                'product:id,name,period_type',
                'schedules' => function ($query) {
                    $query->where('status', '!=', 1)
                        ->select('id', 'loan_id', 'payment_date', 'principal', 'interest', 'payment', 'paid', 'status', 'date_cleared')
                        ->orderBy('payment_date');
                },
                'disbursements' => function ($query) {
                    $query->where('status', 2)->orderBy('created_at', 'desc');
                },
            ])
            ->orderBy('datecreated', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $pageLoans = $loans->getCollection();
        $scheduleIds = $pageLoans->flatMap(fn ($loan) => $loan->schedules->pluck('id'))->all();
        $paidBySchedule = $this->getConfirmedSchedulePayments($scheduleIds, 'repayments');
        $waivedBySchedule = $this->getWaivedLateFeesBySchedule($scheduleIds);
        $memberLoanCounts = $this->getPotentialDuplicateLoanCounts($pageLoans->pluck('member_id')->filter()->all());

        $pageLoans = $pageLoans->map(function ($loan) use ($paidBySchedule, $waivedBySchedule, $memberLoanCounts) {
            return $this->decorateActiveLoan($loan, $paidBySchedule, [], $memberLoanCounts, $waivedBySchedule);
        })->filter(function ($loan) {
            return ((float) ($loan->outstanding_balance ?? 0) != 0.0) || (int) ($loan->status ?? 0) === 2;
        })->values();

        $this->attachLoanFollowUps($pageLoans);
        $this->attachLoanCollateralStatus($pageLoans);

        if ($request->filled('status')) {
            $statusFilter = $request->get('status');
            $pageLoans = $pageLoans->filter(function ($loan) use ($statusFilter) {
                return match ($statusFilter) {
                    'current' => (int) ($loan->risk_dpd ?? 0) === 0,
                    'overdue' => (int) ($loan->risk_dpd ?? 0) > 0,
                    'restructured' => (int) ($loan->restructured ?? 0) === 1,
                    'risk_followup' => (bool) ($loan->requires_follow_up ?? false),
                    'missing_followup' => (bool) ($loan->requires_follow_up ?? false) && !(bool) ($loan->has_follow_up ?? false),
                    'missing_collateral' => !(bool) ($loan->has_collateral ?? false),
                    default => true,
                };
            })->values();
        }

        $loans->setCollection($pageLoans);

        $branches = $this->loanAccessService->branchesForActiveLoanOperations(Branch::active())->orderBy('name')->get();
        $products = Product::loanProducts()->active()->orderBy('name')->get();
        $stats = $this->getFastActivePersonalLoanStats($request);

        return view('admin.loans.active', compact('loans', 'branches', 'products', 'stats'));
    }

    protected function decorateActiveLoan($loan, array $personalPaidBySchedule, array $groupPaidBySchedule = [], array $memberLoanCounts = [], array $waivedBySchedule = [])
    {
        try {
            if (isset($loan->member)) {
                $loan->loan_type = 'personal';
                $loan->borrower_name = trim(($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? ''));
                $loan->phone_number = $loan->member->contact ?? 'N/A';
                $loan->member_id_value = $loan->member_id;
            } elseif (isset($loan->group)) {
                $loan->loan_type = 'group';
                $loan->borrower_name = $loan->group->name ?? 'N/A';
                $loan->phone_number = 'Group Loan';
                $loan->member_id_value = null;
            } else {
                $loan->loan_type = 'unknown';
                $loan->borrower_name = 'N/A';
                $loan->phone_number = 'N/A';
                $loan->member_id_value = null;
            }

            $loan->branch_name = $loan->branch->name ?? 'N/A';
            $loan->product_name = $loan->product->name ?? 'N/A';
            $loan->loan_code = $loan->code;
            $loan->principal_amount = (float) $loan->principal;
            $loan->disbursement_date = $this->getDisbursementDate($loan);
            $loan->is_potential_duplicate = false;

            if (isset($loan->member_id_value, $memberLoanCounts[$loan->member_id_value]) && $memberLoanCounts[$loan->member_id_value] > 1) {
                $loan->is_potential_duplicate = true;
                $loan->duplicate_loans_count = $memberLoanCounts[$loan->member_id_value];
            }

            $paidBySchedule = $loan instanceof GroupLoan ? $groupPaidBySchedule : $personalPaidBySchedule;
            $outstanding = $this->getLoanInstallmentOutstanding($loan, $paidBySchedule, $waivedBySchedule);

            $loan->outstanding_principal = $outstanding['principal'];
            $loan->outstanding_interest = $outstanding['interest'];
            $loan->outstanding_late_fees = $outstanding['late_fees'];
            $loan->outstanding_balance = $outstanding['total'];

            $risk = $this->getActiveLoanRiskProfile($loan);
            $loan->risk_classification = $risk['classification'];
            $loan->risk_dpd = $risk['dpd'];
            $loan->risk_overdue_installments = $risk['overdue_installments'];
            $loan->risk_badge = $risk['badge'];
            $loan->requires_follow_up = $risk['requires_follow_up'];

            $nextOutstandingSchedule = $this->getNextOutstandingSchedule($loan, $paidBySchedule, $waivedBySchedule);
            $loan->next_due_date = $nextOutstandingSchedule['payment_date'] ?? null;
            $loan->next_due_amount = $nextOutstandingSchedule['amount'] ?? 0.0;
            $loan->days_overdue = $nextOutstandingSchedule['days_overdue'] ?? 0;
        } catch (\Exception $e) {
            Log::error("Error processing active loan {$loan->id}: " . $e->getMessage());
            $loan->outstanding_balance = 0;
            $loan->outstanding_principal = 0;
            $loan->outstanding_interest = 0;
            $loan->outstanding_late_fees = 0;
            $loan->next_due_date = null;
            $loan->next_due_amount = 0;
            $loan->days_overdue = 0;
        }

        return $loan;
    }

    protected function getPotentialDuplicateLoanCounts(array $memberIds): array
    {
        $memberIds = array_values(array_unique(array_filter($memberIds)));
        if (empty($memberIds)) {
            return [];
        }

        return PersonalLoan::query()
            ->whereIn('member_id', $memberIds)
            ->whereYear('datecreated', '>=', 2025)
            ->groupBy('member_id')
            ->select('member_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'member_id')
            ->map(fn ($total) => (int) $total)
            ->toArray();
    }

    protected function getWaivedLateFeesBySchedule(array $scheduleIds): array
    {
        $scheduleIds = array_values(array_unique(array_filter($scheduleIds)));
        if (empty($scheduleIds) || !Schema::hasTable('late_fees')) {
            return [];
        }

        return DB::table('late_fees')
            ->whereIn('schedule_id', $scheduleIds)
            ->where('status', 2)
            ->groupBy('schedule_id')
            ->select('schedule_id', DB::raw('SUM(amount) as total_waived'))
            ->pluck('total_waived', 'schedule_id')
            ->map(fn ($amount) => (float) $amount)
            ->toArray();
    }

    protected function getFastActivePersonalLoanStats(Request $request): array
    {
        $cacheKey = 'active-personal-stats:' . auth()->id() . ':' . md5(json_encode($request->only(['search', 'branch', 'product', 'status'])));

        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($request) {
            $query = $this->loanAccessService->scopeActiveLoanQuery(
                DB::table('personal_loans as l'),
                'l.branch_id'
            )
                ->whereIn('l.status', [2, 3])
                ->whereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('loan_schedules as sx')
                        ->whereColumn('sx.loan_id', 'l.id')
                        ->where('sx.status', '!=', 1);
                });

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->leftJoin('members as m', 'm.id', '=', 'l.member_id')
                    ->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('l.code', 'like', "%{$search}%")
                            ->orWhere('m.fname', 'like', "%{$search}%")
                            ->orWhere('m.lname', 'like', "%{$search}%")
                            ->orWhere('m.contact', 'like', "%{$search}%");
                    });
            }

            if ($request->filled('branch')) {
                $query->where('l.branch_id', $request->get('branch'));
            }

            if ($request->filled('product')) {
                $query->where('l.product_type', $request->get('product'));
            }

            $loanIds = (clone $query)->pluck('l.id')->all();
            $totalActive = count($loanIds);

            $principal = 0.0;
            $interest = 0.0;
            $lateFees = 0.0;
            $overdueCount = 0;
            $riskFollowupCount = 0;
            $followedUpCount = 0;
            $followupDueCount = 0;
            $missingCollateralCount = 0;

            if (!empty($loanIds)) {
                $paidSubquery = DB::table('repayments')
                    ->where('amount', '>', 0)
                    ->whereNotIn('status', [-1, 2])
                    ->where(function ($query) {
                        $query->where('status', 1)
                            ->orWhere('payment_status', 'Completed');
                    })
                    ->groupBy('schedule_id')
                    ->select('schedule_id', DB::raw('SUM(amount) as paid'));

                $waivedSubquery = DB::table('late_fees')
                    ->where('status', 2)
                    ->groupBy('schedule_id')
                    ->select('schedule_id', DB::raw('SUM(amount) as waived'));

                $dueDateExpression = "COALESCE(STR_TO_DATE(s.payment_date, '%d-%m-%Y'), DATE(s.payment_date))";
                $daysOverdueExpression = "GREATEST(0, DATEDIFF(CURDATE(), {$dueDateExpression}))";
                $periodsExpression = "
                    CASE
                        WHEN {$daysOverdueExpression} <= 0 THEN 0
                        WHEN pr.period_type = 1 THEN CEIL({$daysOverdueExpression} / 7)
                        WHEN pr.period_type = 2 THEN CEIL({$daysOverdueExpression} / 30)
                        ELSE {$daysOverdueExpression}
                    END
                ";
                $grossLateFeeExpression = "((s.principal + s.interest) * 0.06 * ({$periodsExpression}))";
                $lateFeePaidExpression = "GREATEST(0, COALESCE(p.paid, 0) - s.interest - s.principal)";

                $aggregate = DB::table('loan_schedules as s')
                    ->join('personal_loans as l', 'l.id', '=', 's.loan_id')
                    ->leftJoin('products as pr', 'pr.id', '=', 'l.product_type')
                    ->leftJoinSub($paidSubquery, 'p', 'p.schedule_id', '=', 's.id')
                    ->leftJoinSub($waivedSubquery, 'w', 'w.schedule_id', '=', 's.id')
                    ->whereIn('s.loan_id', $loanIds)
                    ->where('s.status', '!=', 1)
                    ->selectRaw("
                        SUM(GREATEST(0, s.interest - COALESCE(p.paid, 0))) as outstanding_interest,
                        SUM(GREATEST(0, s.principal - GREATEST(0, COALESCE(p.paid, 0) - s.interest))) as outstanding_principal,
                        SUM(GREATEST(0, {$grossLateFeeExpression} - COALESCE(w.waived, 0) - {$lateFeePaidExpression})) as outstanding_late_fees
                    ")
                    ->first();

                $principal = (float) ($aggregate->outstanding_principal ?? 0);
                $interest = (float) ($aggregate->outstanding_interest ?? 0);
                $lateFees = (float) ($aggregate->outstanding_late_fees ?? 0);

                $overdueCount = DB::table('personal_loans as l')
                    ->whereIn('l.id', $loanIds)
                    ->whereExists(function ($subquery) {
                        $subquery->select(DB::raw(1))
                            ->from('loan_schedules as s')
                            ->whereColumn('s.loan_id', 'l.id')
                            ->where('s.status', '!=', 1)
                            ->whereRaw("COALESCE(STR_TO_DATE(s.payment_date, '%d-%m-%Y'), DATE(s.payment_date)) < CURDATE()");
                    })
                    ->count();

                $riskLoanIds = DB::table('personal_loans as l')
                    ->whereIn('l.id', $loanIds)
                    ->whereExists(function ($subquery) {
                        $subquery->select(DB::raw(1))
                            ->from('loan_schedules as s')
                            ->whereColumn('s.loan_id', 'l.id')
                            ->where('s.status', '!=', 1)
                            ->whereRaw("COALESCE(STR_TO_DATE(s.payment_date, '%d-%m-%Y'), DATE(s.payment_date)) < CURDATE()");
                    })
                    ->pluck('l.id')
                    ->all();

                $riskFollowupCount = count($riskLoanIds);

                if (!empty($riskLoanIds) && Schema::hasTable('loan_follow_ups')) {
                    $followedUpCount = DB::table('loan_follow_ups')
                        ->where('loan_type', 'personal')
                        ->whereIn('loan_id', $riskLoanIds)
                        ->distinct()
                        ->count('loan_id');

                    $latestFollowUps = DB::table('loan_follow_ups')
                        ->where('loan_type', 'personal')
                        ->whereIn('loan_id', $riskLoanIds)
                        ->groupBy('loan_id')
                        ->select('loan_id', DB::raw('MAX(id) as id'));

                    $followupDueCount = DB::table('loan_follow_ups as f')
                        ->joinSub($latestFollowUps, 'latest', function ($join) {
                            $join->on('latest.id', '=', 'f.id');
                        })
                        ->whereNotNull('f.next_follow_up_date')
                        ->whereDate('f.next_follow_up_date', '<=', today())
                        ->count();
                }

                if (Schema::hasTable('cash_securities')) {
                    $documentedLoanIds = array_keys(array_filter(
                        $this->getCollateralDocumentCounts($loanIds),
                        fn ($total) => (int) $total > 0
                    ));

                    $missingCollateralCount = DB::table('personal_loans as l')
                        ->whereIn('l.id', $loanIds)
                        ->whereNotExists(function ($subquery) {
                            $subquery->select(DB::raw(1))
                                ->from('cash_securities as cs')
                                ->whereColumn('cs.loan_id', 'l.id')
                                ->where('cs.status', 1)
                                ->where('cs.returned', 0);
                        })
                        ->where(function ($query) use ($documentedLoanIds) {
                            $query->whereRaw("COALESCE(NULLIF(TRIM(l.immovable_assets), ''), NULLIF(TRIM(l.moveable_assets), ''), NULLIF(TRIM(l.intellectual_property), ''), NULLIF(TRIM(l.stocks_collateral), ''), NULLIF(TRIM(l.livestock_collateral), '')) IS NULL");

                            if (!empty($documentedLoanIds)) {
                                $query->orWhereNotIn('l.id', $documentedLoanIds);
                            } else {
                                $query->orWhereRaw('1 = 1');
                            }
                        })
                        ->count();
                }
            }

            return [
                'total_active' => $totalActive,
                'outstanding_amount' => $principal + $interest + $lateFees,
                'outstanding_principal' => $principal,
                'outstanding_interest' => $interest,
                'outstanding_late_fees' => $lateFees,
                'overdue_count' => $overdueCount,
                'risk_followup_count' => $riskFollowupCount,
                'followed_up_count' => $followedUpCount,
                'missing_followup_count' => max(0, $riskFollowupCount - $followedUpCount),
                'followup_due_count' => $followupDueCount,
                'missing_collateral_count' => $missingCollateralCount,
                'collections_today' => $this->getAccessiblePersonalCollectionsToday(),
            ];
        });
    }

    protected function getAccessiblePersonalCollectionsToday(): float
    {
        $query = DB::table('repayments as r')
            ->join('personal_loans as l', 'l.id', '=', 'r.loan_id')
            ->whereDate('r.date_created', today())
            ->where('r.status', 1);

        return (float) ($this->loanAccessService->scopeActiveLoanQuery(
            $query,
            'l.branch_id'
        )->sum('r.amount') ?? 0);
    }

    /**
     * Store a collection follow-up note for an active loan.
     */
    public function storeFollowUp(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => ['required', 'integer'],
            'loan_type' => ['required', 'in:personal,group'],
            'contact_method' => ['required', 'in:call,sms,field_visit,whatsapp,office_visit,other'],
            'outcome' => ['required', 'in:promised_to_pay,willing_to_pay,not_reachable,refused,reschedule_requested,dispute,paid_after_contact,other'],
            'promise_date' => ['nullable', 'date'],
            'promise_amount' => ['nullable', 'numeric', 'min:0'],
            'next_action' => ['nullable', 'in:call_again,send_sms,field_visit,escalate_to_manager,restructure,legal_recovery,none'],
            'next_follow_up_date' => ['nullable', 'date'],
            'sms_sent' => ['nullable', 'boolean'],
            'sms_message' => ['nullable', 'string', 'max:1000'],
            'notes' => ['required', 'string', 'min:5', 'max:3000'],
        ]);

        $loan = $validated['loan_type'] === 'group'
            ? GroupLoan::find($validated['loan_id'])
            : PersonalLoan::find($validated['loan_id']);

        if (!$loan) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found for follow-up.',
                ], 404);
            }

            return back()->with('error', 'Loan not found for follow-up.');
        }

        $this->loanAccessService->ensureLoanAccess($loan);

        $smsRequested = $request->boolean('sms_sent');
        $smsMessage = $validated['sms_message'] ?? null;
        $smsResult = [
            'sent' => false,
            'message' => null,
        ];

        if ($smsRequested) {
            if (!$smsMessage) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please enter the SMS message before sending.',
                    ], 422);
                }

                return back()
                    ->withInput()
                    ->with('error', 'Please enter the SMS message before sending.');
            }

            $smsResult = $this->sendFollowUpSms($loan, $validated['loan_type'], $smsMessage);
        }

        $followUp = LoanFollowUp::create([
            'loan_type' => $validated['loan_type'],
            'loan_id' => $loan->id,
            'member_id' => $validated['loan_type'] === 'personal' ? ($loan->member_id ?? null) : null,
            'branch_id' => $loan->branch_id ?? null,
            'assigned_to' => $loan->assigned_to ?? null,
            'created_by' => auth()->id(),
            'follow_up_at' => now(),
            'contact_method' => $validated['contact_method'],
            'outcome' => $validated['outcome'],
            'willing_to_pay' => in_array($validated['outcome'], ['promised_to_pay', 'willing_to_pay', 'paid_after_contact'], true),
            'promise_date' => $validated['promise_date'] ?? null,
            'promise_amount' => $validated['promise_amount'] ?? null,
            'next_action' => $validated['next_action'] ?? null,
            'next_follow_up_date' => $validated['next_follow_up_date'] ?? null,
            'sms_sent' => $smsResult['sent'],
            'sms_message' => $smsMessage,
            'notes' => $validated['notes'],
        ]);

        $message = 'Follow-up recorded successfully.';
        if ($smsRequested) {
            $message .= ' ' . $smsResult['message'];
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'follow_up' => [
                    'id' => $followUp->id,
                    'loan_id' => $followUp->loan_id,
                    'loan_type' => $followUp->loan_type,
                    'outcome' => $followUp->outcome,
                    'contact_method' => $followUp->contact_method,
                    'created_at' => $followUp->created_at?->format('Y-m-d H:i:s'),
                ],
            ]);
        }

        return back()->with($smsRequested && !$smsResult['sent'] ? 'error' : 'success', $message);
    }

    /**
     * Display repayment schedules for a specific loan
     */
    public function schedules($loanId)
    {
        // Try to find loan in personal_loans first, then group_loans
        $loan = PersonalLoan::with(['member', 'branch', 'product', 'schedules.repayments', 'repayments', 'disbursements' => function($query) {
                    $query->where('status', 2)->orderBy('created_at', 'desc');
                }])
                   ->whereIn('status', [2, 3, 5]) // Disbursed, Completed, or Restructured
                   ->find($loanId);
        
        $loanType = 'personal';
        
        if (!$loan) {
            $loan = GroupLoan::with(['group', 'branch', 'product', 'schedules.repayments', 'repayments', 'disbursements' => function($query) {
                        $query->where('status', 2)->orderBy('created_at', 'desc');
                    }])
                       ->whereIn('status', [2, 3, 5]) // Disbursed, Completed, or Restructured
                       ->find($loanId);
            $loanType = 'group';
        }
        
        if (!$loan) {
            abort(404, 'Loan not found');
        }

        $this->loanAccessService->ensureLoanAccess($loan);

        // Calculate loan details based on loan type
        if ($loanType === 'personal') {
            $loan->borrower_name = $loan->member->fname . ' ' . $loan->member->lname;
            $loan->phone_number = $loan->member->contact;
        } else {
            $loan->borrower_name = $loan->group->name;
            $loan->phone_number = $loan->group->contact ?? 'N/A';
        }
        
        $loan->branch_name = $loan->branch->name ?? 'N/A';
        $loan->product_name = $loan->product->name ?? 'N/A';
        $loan->loan_code = $loan->code;
        $loan->principal_amount = $loan->principal;
        $loan->loan_type = $loanType;
        
        // Set loan details for display
        $loan->interest_rate = $loan->interest ?? 0;
        $loan->loan_term = $loan->period ?? 0;
        
        // Determine period type name (use $loan->period which is the actual column)
        $period_type = $loan->period ?? $loan->period_type ?? '1';
        if ($period_type == '1') {
            $loan->period_type_name = 'weeks';
        } else if ($period_type == '2') {
            $loan->period_type_name = 'months';
        } else if ($period_type == '3') {
            $loan->period_type_name = 'days';
        } else {
            $loan->period_type_name = 'installments';
        }
        
        // Get disbursement date using centralized helper method
        $loan->disbursement_date = $this->getDisbursementDate($loan);
        
        // Calculate days overdue - find first unpaid schedule and check if it's past due
        $firstUnpaid = $loan->schedules()
                            ->where('status', 0)
                            ->orderBy('payment_date')
                            ->first();
        
        if ($firstUnpaid) {
            // Use parsePaymentDate() to handle DD-MM-YYYY format correctly
            // Compare dates only (at midnight) to avoid time-of-day issues
            $dueTimestamp = $this->parsePaymentDate($firstUnpaid->payment_date);
            $dueDate = date('Y-m-d', $dueTimestamp);
            $today = date('Y-m-d');
            
            $dueDateTimestamp = strtotime($dueDate . ' 00:00:00');
            $nowTimestamp = strtotime($today . ' 00:00:00');
            $daysOverdue = floor(($nowTimestamp - $dueDateTimestamp) / (60 * 60 * 24));
            $loan->days_overdue = max(0, (int) $daysOverdue);
        } else {
            $loan->days_overdue = 0;
        }

        // Pre-load all repayment data to avoid N+1 queries
        $scheduleIds = $loan->schedules->pluck('id')->toArray();
        
        // Get total paid per schedule (single query)
        $paidPerSchedule = DB::table('repayments')
            ->whereIn('schedule_id', $scheduleIds)
            ->where('amount', '>', 0)
            ->whereNotIn('status', [-1, 2]) // Exclude INVALID/FAILED records
            ->where(function($query) {
                $query->where('status', 1)
                      ->orWhere('payment_status', 'Completed');
            })
            ->groupBy('schedule_id')
            ->select('schedule_id', DB::raw('SUM(amount) as total_paid'))
            ->pluck('total_paid', 'schedule_id')
            ->toArray();
        
        // Get late fees paid per schedule (separately tracked in late_fees table)
        // CRITICAL FIX: Include late fees paid in the total paid calculation
        $lateFeesPaidPerSchedule = DB::table('late_fees')
            ->whereIn('schedule_id', $scheduleIds)
            ->where('status', 1) // STATUS_PAID
            ->groupBy('schedule_id')
            ->select('schedule_id', DB::raw('SUM(amount) as total_late_fees_paid'))
            ->pluck('total_late_fees_paid', 'schedule_id')
            ->toArray();
        
        // Get pending count per schedule (single query)
        // Detect both old-path (pay_status='Pending') and new MM path (payment_status='Pending')
        $pendingPerSchedule = DB::table('repayments')
            ->whereIn('schedule_id', $scheduleIds)
            ->where('amount', '>', 0)
            ->where('status', 0)
            ->where(function($query) {
                $query->where('pay_status', 'Pending')
                      ->orWhere('payment_status', 'Pending');
            })
            ->groupBy('schedule_id')
            ->select('schedule_id', DB::raw('COUNT(*) as pending_count'))
            ->pluck('pending_count', 'schedule_id')
            ->toArray();
        
        // Get schedules with payment status - EXACT bimsadmin calculation logic
        $principal = floatval($loan->principal); // Running principal balance
        $globalprincipal = floatval($loan->principal); // Global principal for interest calculation
        $schedules = $loan->schedules->map(function($schedule, $index) use ($loan, &$principal, &$globalprincipal, $paidPerSchedule, $lateFeesPaidPerSchedule, $pendingPerSchedule) {
            // 1. Calculate "Principal cal Interest" (reducing balance per period)
            $period = floor($loan->period / 2);
            $pricipalcalIntrest = $period > 0 ? ($loan->principal / $period) : 0;
            
            // 2. Use actual interest from schedule (already calculated correctly during disbursement)
            // DO NOT recalculate - it uses the wrong formula with 1.99999999 multiplier
            $intrestamtpayable = $schedule->interest;
            
            // 3. Calculate periods in arrears
            // First check if P+I has been paid to determine which date to use
            // Use schedule.paid which includes carry-overs from previous schedules
            $paidAmount = floatval($schedule->paid ?? 0);
            $scheduleDue = $schedule->principal + $schedule->interest;
            // Allow small rounding differences (within 1 UGX)
            $principalInterestPaid = $paidAmount >= ($scheduleDue - 1);
            
            // CRITICAL: Late fees freeze ONLY when ENTIRE balance (P+I+Late Fees) = 0
            // DO NOT use schedule.status field - it may be incorrectly set
            // Calculate actual balance first, then determine freeze status
            
            // Quick pre-calculation: Check if balance is likely zero
            $paidAmount = floatval($schedule->paid ?? 0);
            $quickBalanceCheck = ($scheduleDue - $paidAmount); // Rough estimate (excludes late fees)
            
            // Only do full balance calculation if schedule appears paid
            if ($quickBalanceCheck <= 0.01) {
                // Schedule appears fully paid - freeze at payment date
                if ($schedule->date_cleared) {
                    $now = strtotime($schedule->date_cleared);
                } else {
                    // Find last payment date
                    $lastPayment = DB::table('repayments')
                        ->where('schedule_id', $schedule->id)
                        ->where('amount', '>', 0)
                        ->where(function($query) {
                            $query->where('status', 1)
                                  ->orWhere('payment_status', 'Completed');
                        })
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    $now = $lastPayment ? strtotime($lastPayment->date_created) : time();
                }
            } else {
                // Schedule NOT fully paid - late fees continue accumulating
                $now = time();
            }
            
            $your_date = $this->parsePaymentDate($schedule->payment_date);
            $datediff = $now - $your_date;
            $d = floor($datediff / (60 * 60 * 24)); // Days overdue
            
            $dd = 0; // Periods overdue
            if ($d > 0) {
                // Get period type from loan's product (1=Weekly, 2=Monthly, 3=Daily)
                // Ensure we use the product's period_type, defaulting to weekly (not monthly!)
                $period_type = $loan->product ? $loan->product->period_type : '1';
                
                if ($period_type == '1') {
                    // Weekly loans: divide by 7
                    $dd = ceil($d / 7);
                } else if ($period_type == '2') {
                    // Monthly loans: divide by 30
                    $dd = ceil($d / 30);
                } else if ($period_type == '3') {
                    // Daily loans: each day is 1 period
                    $dd = $d;
                } else {
                    // Default to weekly (matches old system)
                    $dd = ceil($d / 7);
                }
            }
            
            // 4. Calculate late fees using helper method
            // CRITICAL RULE: Late fees and periods ONLY freeze when TOTAL BALANCE = 0
            // DO NOT use schedule.status - it may be incorrectly set to 1 when only P+I paid
            // Instead, calculate actual balance and check if it's truly zero
            
            // First, get amount paid to check actual balance
            $paidFromRepayments = floatval($paidPerSchedule[$schedule->id] ?? 0);
            $scheduleDue = $schedule->principal + $schedule->interest;
            
            // Calculate late fee based on current time to check if balance is zero
            $currentLateFeeData = $this->repaymentService->calculateLateFee($schedule, $loan);
            $currentLateFee = $currentLateFeeData['net'];
            $totalBalance = ($scheduleDue + $currentLateFee) - $paidFromRepayments;
            
            // Only freeze if ACTUAL balance is zero (not just status=1)
            if ($totalBalance <= 0.01) {
                // TRUE full payment - freeze at payment date
                if ($schedule->date_cleared) {
                    $paymentDate = strtotime($schedule->date_cleared);
                } else {
                    // Find when schedule was fully paid
                    $payments = DB::table('repayments')
                        ->where('schedule_id', $schedule->id)
                        ->where('status', 1)
                        ->orderBy('id', 'asc')
                        ->get();
                    
                    if (count($payments) > 0) {
                        $lastPayment = $payments->last();
                        $paymentDate = strtotime($lastPayment->date_created);
                    } else {
                        $paymentDate = time();
                    }
                }
                
                $dueDate = $this->parsePaymentDate($schedule->payment_date);
                $daysLateAtPayment = max(0, floor(($paymentDate - $dueDate) / 86400));
                
                $periodsLateAtPayment = 0;
                if ($daysLateAtPayment > 0) {
                    $period_type = $loan->product ? $loan->product->period_type : '1';
                    $periodsLateAtPayment = $period_type == '1' ? ceil($daysLateAtPayment / 7) : ($period_type == '2' ? ceil($daysLateAtPayment / 30) : $daysLateAtPayment);
                }
                
                $latepaymentOriginal = (($schedule->principal + $intrestamtpayable) * 0.06) * $periodsLateAtPayment;
                
                // Check for waivers - only manually waived late fees count
                if ($periodsLateAtPayment > 0) {
                    $totalWaivedAmount = DB::table('late_fees')
                        ->where('schedule_id', $schedule->id)
                        ->where('status', 2) // Status 2 = manually waived by admin
                        ->sum('amount');
                } else {
                    $totalWaivedAmount = 0;
                }
                
                $latepayment = max(0, $latepaymentOriginal - $totalWaivedAmount);
                $lateFeeData = [
                    'days_overdue' => $daysLateAtPayment,
                    'periods_overdue' => $periodsLateAtPayment,
                    'gross' => $latepaymentOriginal,
                    'waived' => $totalWaivedAmount,
                    'net' => $latepayment
                ];
            } else {
                // Balance NOT zero - late fees and periods continue accumulating based on TODAY
                // This includes cases where status=1 but late fees are unpaid
                // Re-use the already-computed current late fee (same call, same args)
                $lateFeeData = $currentLateFeeData;
            }
            
            $latepaymentOriginal = $lateFeeData['gross'];
            $totalWaivedAmount = $lateFeeData['waived'];
            $latepayment = $lateFeeData['net'];
            
            // Store the actual periods used for late fee calculation
            // This should be displayed in "Periods in Arrears" for audit purposes
            $periodsForLateFee = $lateFeeData['periods_overdue'];
            
            // Store both values for summary display
            // DO NOT recalculate periods - periods in arrears should reflect actual time elapsed
            // The waiver reduces the late fee amount, but doesn't change how many periods have passed
            // Keep $dd as calculated above based on actual days overdue
            
            // 5. Get total paid directly from repayments table (what client actually paid)
            // ($paidFromRepayments already set above for the freeze check — same value, reuse it)
            $paidFromLateFees = floatval($lateFeesPaidPerSchedule[$schedule->id] ?? 0);
            
            // IMPORTANT: Use ONLY actual payments from repayments table
            // Ignore old schedule.paid field which has incorrect carry-over data
            $actualPaymentReceived = $paidFromRepayments;
            $totalPaid = $actualPaymentReceived;

            // For historical/imported data, any amount above P+I proves late fees were actually paid
            // Ensure display distribution and total payment reflect real money received
            $principalInterestDue = $schedule->principal + $intrestamtpayable;
            $lateFeesPaidFromActual = max(0, $actualPaymentReceived - $principalInterestDue);
            if ($lateFeesPaidFromActual > 0) {
                $latepayment = max($latepayment, $lateFeesPaidFromActual);
                $totalWaivedAmount = max(0, $latepaymentOriginal - $latepayment);
            }
            
            // Automatic carry-over remains disabled
            // $totalPaid += $carryOverPayment;
            // $carryOverPayment = 0; // Reset after applying
            // 6. Get pending count from pre-loaded data (optimized - no N+1 queries)
            $pendingCount = intval($pendingPerSchedule[$schedule->id] ?? 0);
            
            // 7. Allocate payments - BIMSADMIN ORDER: Late Fees → Interest → Principal
            // CRITICAL FIX: Late fees should only be marked as paid if payment EXCEEDS P+I
            // Don't auto-allocate to late fees unless payment amount proves they were paid
            // IMPORTANT: Use $actualPaymentReceived (not $totalPaid) for distribution display
            // because carry-overs were already distributed on previous schedules
            
            $scheduleDueWithoutLateFees = $schedule->principal + $intrestamtpayable;
            
            if ($totalWaivedAmount > 0 && $latepayment == 0) {
                // All late fees were waived - skip late fee allocation entirely
                // Payment goes directly to Interest → Principal
                $pafterlatepayment = 0; // No late fees paid (they were waived!)
                $afterlatepayment = $actualPaymentReceived; // Use actual payment for distribution
            } elseif ($actualPaymentReceived > $scheduleDueWithoutLateFees) {
                // Payment EXCEEDS principal + interest, so excess goes to late fees
                $excessPayment = $actualPaymentReceived - $scheduleDueWithoutLateFees;
                if ($excessPayment >= $latepayment) {
                    $pafterlatepayment = $latepayment; // Full late fee paid
                    $afterlatepayment = $scheduleDueWithoutLateFees; // P+I fully available
                } else {
                    $pafterlatepayment = $excessPayment; // Partial late fee paid
                    $afterlatepayment = $scheduleDueWithoutLateFees; // P+I fully available
                }
            } else {
                // Payment does NOT exceed P+I, so NO late fees were paid
                $pafterlatepayment = 0; // Late fees NOT paid
                $afterlatepayment = $actualPaymentReceived; // Use actual payment for distribution
            }
            
            // Interest paid
            $afterinterestpayment = $afterlatepayment - $intrestamtpayable;
            if ($afterinterestpayment > 0) {
                $pafterinterestpayment = $intrestamtpayable; // Full interest covered
            } else {
                $pafterinterestpayment = $afterlatepayment; // Partial payment
                $afterinterestpayment = 0;
            }
            
            // Principal paid
            $afterprinicpalpayment = $afterinterestpayment - $schedule->principal;
            if ($afterprinicpalpayment > 0) {
                $pafterprinicpalpayment = $schedule->principal; // Full principal covered
            } else {
                $pafterprinicpalpayment = $afterinterestpayment; // Partial payment
                $afterprinicpalpayment = 0;
            }
            
            // 8. Calculate total balance
            $scheduleDueNet = $schedule->principal + $intrestamtpayable + $latepayment;
            $scheduleDueGross = $schedule->principal + $intrestamtpayable + $latepaymentOriginal;
            $act_bal = $scheduleDueNet - $totalPaid;

            // 8a. Show EXCESS only if client paid above GROSS due (not just above net due after waivers)
            $excessAmount = $totalPaid - $scheduleDueGross;
            $schedule->excess_amount = $excessAmount > 1 ? $excessAmount : 0;

            if ($act_bal < 0) {
                $act_bal = 0; // Balance cannot be negative
            }
            
            // 8b. Adjust arrears period display
            // CRITICAL: Periods ONLY stop accumulating when TOTAL BALANCE = 0
            // Use $act_bal (already calculated above) to determine if truly paid
            // DO NOT rely on schedule.status which may be incorrectly set
            if ($act_bal <= 0.01) {
                // TRUE full payment (balance is zero) - periods are frozen
                $displayPeriods = $periodsForLateFee; // Frozen value from payment date
            } else {
                // Balance NOT zero - periods continue accumulating
                $displayPeriods = $periodsForLateFee; // Current value from today's date
            }
            
            // 9. Attach calculated values to schedule
            $schedule->pricipalcalIntrest = $pricipalcalIntrest;
            $schedule->globalprincipal = $globalprincipal;
            $schedule->intrestamtpayable = $intrestamtpayable;
            $schedule->periods_in_arrears = $displayPeriods;
            $schedule->penalty_original = $latepaymentOriginal; // Late fee BEFORE waiver
            $schedule->penalty_waived = $totalWaivedAmount; // Amount waived by admin
            $schedule->penalty = $latepayment; // Late fee AFTER waiver (what client owes)
            $schedule->total_payment = $schedule->principal + $intrestamtpayable + $latepayment;
            $schedule->principal_paid = $pafterprinicpalpayment;
            $schedule->interest_paid = $pafterinterestpayment;
            $schedule->penalty_paid = $pafterlatepayment;
            $schedule->paid = $actualPaymentReceived; // What client actually paid from repayments table
            $schedule->principal_balance = $principal;
            $schedule->pending_count = $pendingCount;
            
            // A schedule is "Paid" only when the TOTAL balance (P+I + late fees) is settled.
            // Exception: for closed loans (status=3), trust the DB schedule status=1.
            // This prevents abandoned pending repayments from showing Check/Retry on already-paid
            // schedules when the loan is fully settled (loan closed by checkAndCloseLoanIfComplete).
            $dbScheduleStatus = $schedule->status; // Original DB value — not yet overridden
            $trustDbPaid = ($loan->status == 3 && $dbScheduleStatus == 1);
            if ($trustDbPaid) {
                $act_bal = 0; // Treat balance as settled for closed-loan paid schedules
            }
            $isFullyPaid = $act_bal <= 1;
            $schedule->total_balance = $act_bal;
            // Track separately whether P+I has been paid (even if late fees are still outstanding)
            $piDue = $schedule->principal + $intrestamtpayable;
            $schedule->pi_paid = $piDue > 0 && $paidFromRepayments >= $piDue - 1;
            $schedule->status = $isFullyPaid ? 1 : 0;
            $schedule->payment_status = $isFullyPaid ? 'paid' : 'pending';
            $schedule->is_overdue = $d > 0 && !$isFullyPaid;
            $schedule->due_date = $schedule->payment_date;
            $schedule->due_amount = $schedule->principal + $intrestamtpayable;
            $schedule->penalty_amount = $latepayment;
            $schedule->days_overdue = $d;
            
            // 10. Update running balances for next iteration
            if ($principal > 0) {
                $principal -= $schedule->principal;
                if ($principal < 0) $principal = 0;
            }
            
            if ($globalprincipal > 0) {
                $globalprincipal -= $pricipalcalIntrest;
                if ($globalprincipal < 0) $globalprincipal = 0;
            }
            
            return $schedule;
        });

        // Find next due payment
        $nextDue = $schedules->where('payment_status', 'pending')
                            ->sortBy('payment_date')
                            ->first();

        // Calculate overdue stats - filter unpaid schedules with past payment dates
        $today = now()->format('Y-m-d');
        $overdueSchedules = $schedules->filter(function($schedule) use ($today) {
            return $schedule->status == 0 && $schedule->payment_date < $today;
        });
        $overdueCount = $overdueSchedules->count();
        $overdueAmount = $overdueSchedules->sum(function($schedule) {
            return $schedule->due_amount + $schedule->penalty_amount;
        });

        // Calculate late fees summary
        // Show ORIGINAL late fees (before waiver) for clarity
        $totalLateFees = $schedules->sum('penalty_original'); // Total BEFORE waiver
        $lateFeesWaived = $schedules->sum('penalty_waived'); // Total waived by admin
        $lateFeesPaid = $schedules->sum('penalty_paid'); // Total actually paid by client

        // RECALCULATE SUMMARY from processed schedules (with freeze logic applied)
        $totalDue = $schedules->sum('total_payment'); // Use calculated total_payment from each schedule
        $totalOutstanding = $schedules->sum(function($schedule) {
            return max(0, $schedule->total_balance); // Sum positive balances only
        });
        $totalPaid = Repayment::where('loan_id', $loan->id)->where('status', 1)->sum('amount');
        
        $loan->total_payable = $totalDue;
        $loan->outstanding_balance = $totalOutstanding;
        $loan->amount_paid = $totalPaid;
        $loan->payment_percentage = $totalDue > 0 ? ($totalPaid / $totalDue) * 100 : 0;

        // DEBUG: Output values before returning view
        \Log::info("After Recalc - Loan {$loan->id}: total_payable={$loan->total_payable}, outstanding={$loan->outstanding_balance}, paid={$loan->amount_paid}");

        return view('admin.loans.repayments.schedules', compact(
            'loan', 'schedules', 'nextDue', 'overdueCount', 'overdueAmount',
            'totalLateFees', 'lateFeesWaived', 'lateFeesPaid'
        ));
    }

    /**
     * Get next schedule for a loan (AJAX)
     */
    public function getNextSchedule($loanId)
    {
        try {
            // Find loan from either table
            $result = $this->findLoanById($loanId);
            $loan = $result['loan'];
            
            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ], 404);
            }

            $this->loanAccessService->ensureLoanAccess($loan);

            $nextSchedule = LoanSchedule::where('loan_id', $loanId)
                                       ->where('status', 0) // Unpaid
                                       ->orderBy('payment_date')
                                       ->first();
            
            if ($nextSchedule) {
                // Calculate dynamic late fees so the quick-repay button shows the full amount owed
                if (method_exists($loan, 'loadMissing')) {
                    $loan->loadMissing('product');
                }
                $nsLateFeeData = $this->repaymentService->calculateLateFee($nextSchedule, $loan);
                $nsLateFees = $nsLateFeeData['net'];

                // How much has already been paid on this schedule
                $nsAlreadyPaid = floatval(
                    DB::table('repayments')
                        ->where('schedule_id', $nextSchedule->id)
                        ->where('amount', '>', 0)
                        ->where(function ($q) {
                            $q->where('status', 1)->orWhere('payment_status', 'Completed');
                        })
                        ->sum('amount')
                );

                $nsTotalDue    = $nextSchedule->payment + $nsLateFees;
                $nsTotalBalance = max(0, $nsTotalDue - $nsAlreadyPaid);

                return response()->json([
                    'success' => true,
                    'schedule' => [
                        'id'              => $nextSchedule->id,
                        'due_date'        => \Carbon\Carbon::parse($nextSchedule->payment_date)->format('M d, Y'),
                        'payment_date'    => $nextSchedule->payment_date,
                        'expected_amount' => number_format($nsTotalBalance, 2),
                        'payment'         => $nsTotalBalance,  // Full balance: P+I + late fees - already paid
                        'payment_amount'  => $nsTotalBalance,
                        'principal'       => $nextSchedule->principal ?? 0,
                        'interest'        => $nextSchedule->interest ?? 0,
                        'penalty'         => $nsLateFees,
                        'already_paid'    => $nsAlreadyPaid,
                    ]
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No pending schedules found for this loan.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process quick repayment (AJAX)
     */
    public function quickRepayment(Request $request)
    {
        // Accept both numeric and string payment methods
        $paymentMethod = $request->input('payment_method') ?: $request->input('type');
        
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable',
            'type' => 'nullable',
            'network' => 'nullable|in:MTN,AIRTEL',
            'phone' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find loan from either personal_loans or group_loans
        $loan = PersonalLoan::find($request->loan_id);
        if (!$loan) {
            $loan = GroupLoan::find($request->loan_id);
        }
        
        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        $this->loanAccessService->ensureLoanAccess($loan);

        DB::beginTransaction();

        try {
            // Convert payment method to numeric code
            // Legacy system: 1=cash, 2=mobile_money, 3=bank/cheque
            $paymentTypeCode = is_numeric($paymentMethod) 
                ? (int)$paymentMethod 
                : $this->getPaymentTypeCode($paymentMethod);

            if (in_array($paymentTypeCode, [1, 3], true) && !auth()->user()?->isSuperAdmin()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only the Super Administrator can confirm cash or bank repayment records. Please use Mobile Money.'
                ], 403);
            }
            
            $scheduleId = (int) ($request->input('schedule_id') ?: $request->input('s_id') ?: 0);
            if ($scheduleId <= 0) {
                $nextSchedule = LoanSchedule::where('loan_id', $loan->id)
                    ->where('status', 0)
                    ->orderBy('payment_date')
                    ->orderBy('id')
                    ->first();
                $scheduleId = $nextSchedule ? (int) $nextSchedule->id : 0;
            }

            if ($scheduleId <= 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No pending repayment schedule found for this loan.'
                ], 422);
            }

            $repaymentData = [
                'type' => $paymentTypeCode,
                'details' => $request->input('notes') ?: $request->input('details', 'Quick repayment'),
                'loan_id' => $request->loan_id,
                'schedule_id' => $scheduleId,
                'amount' => $request->amount,
                'date_created' => now(),
                'added_by' => auth()->id(),
                'platform' => $request->input('platform', 'Web'),
            ];

            // Handle mobile money (type = 2 in legacy system)
            if ($paymentTypeCode == 2) {
                $phone = $request->input('phone');
                $network = $request->input('network');
                
                if (!$phone) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phone number is required for mobile money payments.'
                    ], 422);
                }
                
                if (!$network) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Network is required for mobile money payments.'
                    ], 422);
                }
                
                $mobileMoneyResult = $this->processMobileMoneyCollection(
                    $loan,
                    $request->amount,
                    $phone,
                    $network
                );

                if ($mobileMoneyResult['success']) {
                    // Use FlexiPay-generated reference
                    $flexipayReference = $mobileMoneyResult['reference'];
                    $flexipaySecondaryReference = $mobileMoneyResult['flexipay_ref'] ?? null;
                    
                    $repaymentData['status'] = 0; // Pending mobile money confirmation
                    $repaymentData['txn_id'] = $flexipayReference;
                    $repaymentData['transaction_reference'] = $flexipaySecondaryReference ?: $flexipayReference;
                    $repaymentData['pay_status'] = 'PENDING';
                    $repaymentData['pay_message'] = 'Mobile money collection initiated - check your phone';
                    
                    // Increment pending_count on the schedule (bimsadmin style)
                    DB::table('loan_schedules')
                        ->where('id', $scheduleId)
                        ->increment('pending_count');
                    
                    // Create raw_payments record for tracking (bimsadmin style)
                    RawPayment::create([
                        'trans_id' => $mobileMoneyResult['reference'],
                        'phone' => $this->mobileMoneyService->formatPhoneNumber($phone),
                        'amount' => $request->amount,
                        'ref' => $mobileMoneyResult['flexipay_ref'] ?? '',
                        'message' => 'Payment initiated',
                        'status' => 'Processed', // Match bimsadmin format
                        'pay_status' => '00', // Actual status code - pending
                        'pay_message' => 'Completed successfully',
                        'date_created' => now(),
                        'type' => 'repayment', // Must be 'repayment' not 'collection'
                        'direction' => 'cash_in',
                        'added_by' => auth()->id(),
                        'raw_message' => serialize(['schedule_id' => $scheduleId, 'loan_id' => $request->loan_id, 'member_id' => $loan->member_id]),
                    ]);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile money collection failed: ' . $mobileMoneyResult['message']
                    ], 400);
                }
            } else {
                // For cash/bank transfers/cheque, mark as confirmed - generate truly unique reference
                // Format: CASH/BANK + timestamp (microseconds) + random suffix
                $microtime = (int)(microtime(true) * 10000);
                $random = mt_rand(1000, 9999);
                $reference = 'EbP' . str_pad($microtime, 8, '0', STR_PAD_LEFT) . str_pad($random, 4, '0', STR_PAD_LEFT);
                
                $repaymentData['status'] = 1;
                $repaymentData['payment_status'] = 'Confirmed';
                $repaymentData['pay_status'] = 'SUCCESS';
                $repaymentData['pay_message'] = 'Cash/Bank payment confirmed by ' . auth()->user()->name;
                $repaymentData['txn_id'] = $reference;
                $repaymentData['transaction_reference'] = $reference; // Set both fields for consistency
            }

            $repayment = Repayment::create($repaymentData);
            $glPosted = true;

            // Update loan balance and schedules if confirmed
            if ($repaymentData['status'] == 1) {
                // NOTE: personal_loans table doesn't have 'paid' column
                // Payment tracking is done via loan_schedules.paid only
                $this->updateLoanSchedules($loan, $repayment);
                
                // Check if loan is fully paid by checking ALL schedules
                $this->repaymentService->checkAndCloseLoanIfComplete($loan->id);
                
                $glPosted = $this->postGLEntry($repayment, $loan);
            }

            DB::commit();

            $message = "Payment recorded successfully.";
            if ($paymentTypeCode == 2) {
                $message = "Collection request sent to " . $request->phone . ". Please check your phone and enter your Mobile Money PIN to complete the payment.";
            }

            $jsonResponse = [
                'success' => true,
                'message' => $message,
                'repayment_id' => $repayment->id,
                'payment_type' => $paymentTypeCode,
            ];
            if (!$glPosted) {
                $jsonResponse['gl_warning'] = 'GL journal entry could not be posted automatically. Please post manually via Journal Entries.';
            }
            return response()->json($jsonResponse);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Quick repayment failed', [
                'loan_id' => $request->loan_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Repayment processing failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Store repayment from schedules view
     */
    public function storeRepayment(Request $request)
    {
        // Log incoming request for debugging
        \Log::info('storeRepayment called', [
            'all_data' => $request->all(),
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'user_id' => auth()->id(),
            'user_roles' => auth()->user()->getRoleNames(),
            'has_s_id' => $request->has('s_id'),
            'has_type' => $request->has('type'),
            'has_amount' => $request->has('amount'),
            'has_details' => $request->has('details')
        ]);

        // Handle bimsadmin-style form submission (POST with s_id, type, details, amount, medium)
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|integer',
            'member_id' => 'nullable|exists:members,id',
            's_id' => 'nullable|exists:loan_schedules,id', // Made nullable for old loans without schedules
            'amount' => 'required|numeric|min:1',
            'type' => 'required|integer|in:1,2,3', // 1=cash, 2=mobile_money, 3=bank
            'medium' => 'nullable|integer|in:1,2', // 1=Airtel, 2=MTN (only for mobile money)
            'details' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            // Check if it's an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . $validator->errors()->first()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fill in all required fields correctly.');
        }

        // Security check: only the Super Administrator can use Cash (1) or Bank Transfer (3).
        if (in_array((int) $request->type, [1, 3], true) && !auth()->user()?->isSuperAdmin()) {
            \Log::warning('Unauthorized payment type attempt', [
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'payment_type' => $request->type,
                'loan_id' => $request->loan_id
            ]);
            
            // Check if it's an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only the Super Administrator can confirm Cash or Bank Transfer payments. Please use Mobile Money.'
                ], 403);
            }
            
            return redirect()->back()
                ->with('error', 'Access denied. Only the Super Administrator can confirm Cash or Bank Transfer payments. Please use Mobile Money.')
                ->withInput();
        }

        // Find loan from either personal_loans or group_loans
        $loan = PersonalLoan::find($request->loan_id);
        $loanType = 'personal';
        
        if (!$loan) {
            $loan = GroupLoan::find($request->loan_id);
            $loanType = 'group';
        }
        
        if (!$loan) {
            return redirect()->back()
                ->with('error', 'Loan not found')
                ->withInput();
        }

        $this->loanAccessService->ensureLoanAccess($loan);

        // SECURITY: Prevent payments on stopped loans
        if ($loan->status == 6) {
            \Log::warning('Attempted payment on stopped loan', [
                'loan_id' => $loan->id,
                'user_id' => auth()->id()
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This loan has been stopped and cannot receive payments. Please contact management for assistance.'
                ], 403);
            }
            
            return redirect()->back()
                ->with('error', 'This loan has been stopped and cannot receive payments. Please contact management for assistance.')
                ->withInput();
        }
        
        // Auto-find schedule if not provided (for old loans)
        if (!$request->s_id) {
            // Find first unpaid schedule
            $schedule = LoanSchedule::where('loan_id', $request->loan_id)
                ->where('status', 0) // Pending
                ->orderBy('id')
                ->first();
            
            if (!$schedule) {
                return redirect()->back()
                    ->with('error', 'No pending payment schedules found for this loan.')
                    ->withInput();
            }
            
            \Log::info('Auto-selected schedule for old loan', [
                'loan_id' => $request->loan_id,
                'schedule_id' => $schedule->id
            ]);
        } else {
            $schedule = LoanSchedule::findOrFail($request->s_id);
        }
        
        // SEQUENTIAL PAYMENT ENFORCEMENT: Check if all earlier schedules are paid
        // Users MUST pay schedules in chronological order (by payment_date)
        $sequenceCheck = $this->checkEarlierSchedulesPaid($schedule->id, $request->loan_id);
        
        if (!$sequenceCheck['allowed']) {
            \Log::warning('Sequential payment rule violated', [
                'user_id' => auth()->id(),
                'loan_id' => $request->loan_id,
                'attempted_schedule_id' => $schedule->id,
                'unpaid_earlier_schedule_id' => $sequenceCheck['unpaid_schedule']->id ?? null,
                'message' => $sequenceCheck['message']
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $sequenceCheck['message']
                ], 422);
            }
            
            return redirect()->back()
                ->with('error', $sequenceCheck['message'])
                ->withInput();
        }
        
        // VALIDATION: Prevent excess payments - only allow up to total amount owed
        // Calculate what's actually owed on this schedule (P + I + Late Fees - Already Paid)
        $scheduleDue = $schedule->principal + $schedule->interest;
        
        // Use successful repayments only (status=1 OR payment_status=Completed).
        // Exclude INVALID (status=-1) and FAILED (status=2) records even if
        // payment_status was incorrectly set to 'Completed' by the old system.
        $alreadyPaid = floatval(
            DB::table('repayments')
                ->where('schedule_id', $schedule->id)
                ->where('amount', '>', 0)
                ->whereNotIn('status', [-1, 2])
                ->where(function($query) {
                    $query->where('status', 1)
                          ->orWhere('payment_status', 'Completed');
                })
                ->sum('amount')
        );
        
        // Calculate late fees
        if (method_exists($loan, 'loadMissing')) {
            $loan->loadMissing('product');
        }
        $lateFeeData = $this->repaymentService->calculateLateFee($schedule, $loan);
        $lateFees = $lateFeeData['net']; // After waivers
        
        $totalOwed = ($scheduleDue + $lateFees) - $alreadyPaid;
        
        // Allow small rounding tolerance (1 UGX) and ensure totalOwed is not negative
        $totalOwed = max(0, $totalOwed);
        
        if ($request->amount > ($totalOwed + 1)) {
            $errorMessage = sprintf(
                'Excess payments not allowed. Payment amount (UGX %s) exceeds what is owed (UGX %s). Please pay the exact amount.',
                number_format($request->amount, 0),
                number_format($totalOwed, 0)
            );
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'max_amount' => $totalOwed
                ], 422);
            }
            
            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }

        if ($request->amount < ($totalOwed - 1)) {
            $errorMessage = sprintf(
                'Partial payments not allowed. Payment amount (UGX %s) is less than what is owed (UGX %s). Please pay the exact amount.',
                number_format($request->amount, 0),
                number_format($totalOwed, 0)
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'required_amount' => $totalOwed
                ], 422);
            }

            return redirect()->back()
                ->with('error', $errorMessage)
                ->withInput();
        }
        
        // Get member/group for contact details
        $contactDetails = $this->getLoanContactDetails($loan, $loanType);
        $phoneNumber = $contactDetails['phone'];
        $memberName = $contactDetails['name'];

        DB::beginTransaction();

        try {
            // Determine network name from medium (for mobile money)
            $network = null;
            if ($request->type == 2 && $request->medium) {
                $network = $this->getNetworkFromMedium($request->medium);
            }

            $repaymentData = [
                'type' => $request->type, // 1=cash, 2=mobile_money, 3=bank
                'details' => $request->details,
                'loan_id' => $request->loan_id,
                'schedule_id' => $schedule->id, // Use the schedule ID we found/validated
                'member_id' => $loanType === 'personal' ? $loan->member_id : null,
                'amount' => $request->amount,
                'date_created' => now(),
                'added_by' => auth()->id(),
                'platform' => 'Web',
            ];
            
            \Log::info('Creating repayment for loan', [
                'loan_id' => $request->loan_id,
                'loan_code' => $loan->code,
                'schedule_id' => $schedule->id,
                'amount' => $request->amount,
                'payment_type' => $request->type,
                'is_old_loan' => !$request->s_id // True if we auto-selected schedule
            ]);

            // Handle mobile money - initiate collection
            if ($request->type == 2) {
                // $memberName and $phoneNumber already set above based on loan type
                
                $mobileMoneyResult = $this->processMobileMoneyCollection(
                    $loan,
                    $request->amount,
                    $phoneNumber,
                    $network
                );

                if ($mobileMoneyResult['success']) {
                    // Use FlexiPay-generated reference (format: EbP########## or transactionReferenceNumber)
                    $flexipayReference = $mobileMoneyResult['reference'];
                    $flexipaySecondaryReference = $mobileMoneyResult['flexipay_ref'] ?? null;
                    
                    // CRITICAL VALIDATION: Never save mobile money payment without valid FlexiPay reference
                    try {
                        $this->validateFlexiPayReference($flexipayReference, $request->loan_id);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        if ($request->ajax() || $request->wantsJson()) {
                            return response()->json([
                                'success' => false,
                                'message' => $e->getMessage()
                            ], 400);
                        }
                        return redirect()->back()->with('error', $e->getMessage());
                    }
                    
                    $repaymentData['status'] = 0; // Pending
                    $repaymentData['txn_id'] = $flexipayReference;
                    $repaymentData['transaction_reference'] = $flexipaySecondaryReference ?: $flexipayReference;
                    $repaymentData['pay_status'] = 'PENDING';
                    $repaymentData['pay_message'] = 'Mobile money collection initiated';
                    $repaymentData['network'] = $network;
                    $repaymentData['payment_phone'] = $phoneNumber;
                    
                    // Increment pending_count on the schedule (bimsadmin style)
                    $schedule->increment('pending_count');
                    
                    // Create the pending repayment record so the callback can find and approve it
                    $repayment = Repayment::create($repaymentData);

                    // Create raw_payments record for tracking (bimsadmin format)
                    \App\Models\RawPayment::create([
                        'trans_id' => $mobileMoneyResult['reference'], // THIS is what CheckTransactions searches for
                        'phone' => $phoneNumber,
                        'amount' => $request->amount,
                        'ref' => $mobileMoneyResult['flexipay_ref'] ?? '',
                        'message' => 'Repayment initiated',
                        'status' => 'Processed', // Match bimsadmin format
                        'pay_status' => '00', // Actual status code - pending (CheckTransactions looks for this)
                        'pay_message' => 'Completed successfully',
                        'date_created' => now(),
                        'type' => 'repayment', // Must be 'repayment' for CheckTransactions filter
                        'direction' => 'cash_in',
                        'added_by' => auth()->id(),
                        'raw_message' => serialize(['schedule_id' => $request->s_id, 'loan_id' => $request->loan_id, 'member_id' => $request->member_id]),
                    ]);
                    
                    DB::commit();
                    
                    // Return JSON for AJAX request
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Mobile money collection initiated',
                            'transaction_id' => $mobileMoneyResult['reference'],
                            'repayment_id' => $repayment->id,
                        ]);
                    }
                } else {
                    DB::rollBack();
                    
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Mobile money collection failed: ' . $mobileMoneyResult['message']
                        ], 400);
                    }
                    
                    return redirect()->back()
                        ->with('error', 'Mobile money collection failed: ' . $mobileMoneyResult['message']);
                }
            } else {
                // Cash (type=1) or Bank Transfer (type=3) - confirmed by Super Admin only
                // Generate truly unique reference using microtime + random + schedule ID
                $microFraction = intval((microtime(true) - intval(microtime(true))) * 1000000);
                $reference = 'EbP' . str_pad((time() % 1000000000) . str_pad($microFraction % 1000, 4, '0', STR_PAD_LEFT), 10, '0', STR_PAD_LEFT);
                
                $paymentMethodName = $request->type == 1 ? 'Cash' : 'Bank Transfer';
                
                $repaymentData['status'] = 1; // Confirmed immediately
                $repaymentData['payment_status'] = 'Confirmed';
                $repaymentData['pay_status'] = 'SUCCESS';
                $repaymentData['pay_message'] = $paymentMethodName . ' payment confirmed by ' . auth()->user()->name;
                $repaymentData['txn_id'] = $reference;
                $repaymentData['transaction_reference'] = $reference; // Also set this for consistency
                
                \Log::info('Cash/Bank payment confirmed instantly', [
                    'loan_id' => $request->loan_id,
                    'loan_code' => $loan->code,
                    'schedule_id' => $schedule->id,
                    'amount' => $request->amount,
                    'payment_type' => $paymentMethodName,
                    'confirmed_by' => auth()->user()->name,
                    'reference' => $reference
                ]);

                // Idempotency guard — prevent duplicate records from race-condition double-clicks.
                // Lock the schedule row so concurrent requests queue here rather than both proceeding.
                \DB::table('loan_schedules')->where('id', $schedule->id)->lockForUpdate()->first();

                $existingConfirmed = Repayment::where('schedule_id', $schedule->id)
                    ->where('status', 1)
                    ->where('amount', $totalOwed)
                    ->where('added_by', auth()->id())
                    ->where('date_created', '>=', now()->subSeconds(30))
                    ->exists();

                if ($existingConfirmed) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'This payment was already recorded. Please refresh the page.'], 409);
                }

                // Use server-calculated outstanding balance as the canonical payment amount.
                // This prevents partial-payment residuals caused by client-supplied amounts
                // and stale schedule.paid counter drift from prior payBalance() calls.
                $repaymentData['amount'] = $totalOwed;

                $repayment = Repayment::create($repaymentData);

                // Full balance is now paid — mark schedule closed and freeze late-fee accrual.
                // We use schedule.payment (canonical P+I column) for the paid field and set
                // date_cleared so calculateLateFee() freezes at today rather than continuing
                // to accumulate into future periods.
                $schedule->update([
                    'paid'         => $schedule->payment,
                    'status'       => 1,
                    'date_cleared' => now(),
                ]);
                
                // Check if loan is fully paid by checking ALL schedules
                $this->repaymentService->checkAndCloseLoanIfComplete($loan->id);

                $this->postGLEntry($repayment, $loan, ['loan_id' => $loan->id]);
                
                DB::commit();
                
                // Return JSON for AJAX
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $paymentMethodName . ' payment of UGX ' . number_format($request->amount) . ' has been confirmed and recorded successfully. Schedule updated to PAID.',
                        'receipt_url' => route('admin.repayments.receipt', $repayment->id)
                    ]);
                }
            }

            $message = $request->type == 2 
                ? "Mobile Money collection request sent successfully. Awaiting customer confirmation."
                : ($request->type == 1 ? "Cash" : "Bank Transfer") . " payment of UGX " . number_format($request->amount) . " confirmed and recorded successfully!";

            return redirect()->route('admin.loans.repayments.schedules', $request->loan_id)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Repayment storage failed', [
                'loan_id' => $request->loan_id,
                'schedule_id' => $request->s_id,
                'amount' => $request->amount,
                'type' => $request->type,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Check if it's an AJAX request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment recording failed: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Payment recording failed: ' . $e->getMessage());
        }
    }

    /**
     * Process partial payment
     */
    /**
     * Pay outstanding balances for specific schedules
     */
    public function payBalance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:mobile_money,cash,bank_transfer',
            'schedules' => 'required|string',
            'txn_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'medium' => 'nullable|integer|in:1,2', // 1=Airtel, 2=MTN (required for mobile money)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find loan from either table
        $result = $this->findLoanById($request->loan_id);
        $loan = $result['loan'];
        $loanType = $result['type'];
        
        if (!$loan) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found'
            ], 404);
        }

        $this->loanAccessService->ensureLoanAccess($loan);

        // Parse schedules JSON
        $schedules = json_decode($request->schedules, true);
        if (!is_array($schedules) || count($schedules) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid schedules data'
            ], 422);
        }

        $scheduleBalances = [];
        $requiredTotal = 0.0;
        foreach ($schedules as $scheduleData) {
            $scheduleId = (int) ($scheduleData['schedule_id'] ?? 0);
            $schedule = LoanSchedule::find($scheduleId);

            if (!$schedule || (int) $schedule->loan_id !== (int) $loan->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'One of the selected schedules does not belong to this loan.'
                ], 422);
            }

            $components = $this->repaymentService->getScheduleOutstandingComponents($schedule, $loan);
            $balance = round((float) $components['outstanding']);

            if ($balance <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'One of the selected schedules is already fully paid.'
                ], 422);
            }

            $scheduleBalances[$scheduleId] = $balance;
            $requiredTotal += $balance;
        }

        if (abs((float) $request->amount - $requiredTotal) > 1) {
            return response()->json([
                'success' => false,
                'message' => 'Payment must equal the exact selected schedule balance: UGX ' . number_format($requiredTotal, 0),
                'required_amount' => $requiredTotal,
            ], 422);
        }

        // Get payment type code
        $paymentTypeCode = $this->getPaymentTypeCode($request->payment_method);

        if (in_array($paymentTypeCode, [1, 3], true) && !auth()->user()?->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only the Super Administrator can confirm cash or bank balance payments. Please use Mobile Money.'
            ], 403);
        }

        // For mobile money, get member's phone number and initiate collection first
        if ($paymentTypeCode == 2) {
            // Get member/group phone number
            $contactDetails = $this->getLoanContactDetails($loan, $loanType);
            $phoneNumber = $contactDetails['phone'];
            $memberName = $contactDetails['name'];

            if (empty($phoneNumber)) {
                \Log::error('Balance Payment - Phone number missing', [
                    'loan_id' => $loan->id,
                    'loan_type' => $loanType
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Member phone number not found'
                ], 400);
            }

            // Determine network from medium (required for mobile money)
            if (!$request->medium) {
                \Log::error('Balance Payment - Network medium missing', [
                    'loan_id' => $loan->id,
                    'request_data' => $request->all()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile money network not specified. Please ensure network is detected.'
                ], 400);
            }
            $network = $this->getNetworkFromMedium($request->medium);

            \Log::info('Balance Payment - Initiating mobile money collection', [
                'loan_id' => $loan->id,
                'amount' => $request->amount,
                'phone' => $phoneNumber,
                'network' => $network,
                'medium' => $request->medium
            ]);

            // Round amount to whole number (FlexiPay doesn't accept decimals)
            $roundedAmount = round(floatval($request->amount));

            // Initiate mobile money collection
            $mobileMoneyResult = $this->processMobileMoneyCollection(
                $loan,
                $roundedAmount,
                $phoneNumber,
                $network
            );

            if (!$mobileMoneyResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mobile money collection failed: ' . $mobileMoneyResult['message']
                ], 400);
            }

            // Use FlexiPay-generated reference
            $flexipayReference = $mobileMoneyResult['reference'];
            $flexipaySecondaryReference = $mobileMoneyResult['flexipay_ref'] ?? null;

            // CRITICAL VALIDATION: Never save mobile money payment without valid FlexiPay reference
            try {
                $this->validateFlexiPayReference($flexipayReference, $loan->id);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 400);
            }
        }

        DB::beginTransaction();

        try {
            $paymentsCreated = 0;
            $schedulesPaid = [];

            // Process each selected schedule
            foreach ($schedules as $scheduleData) {
                $scheduleId = $scheduleData['schedule_id'];
                $scheduleBalance = (float) ($scheduleBalances[$scheduleId] ?? 0);

                // Get the actual schedule from database
                $schedule = LoanSchedule::find($scheduleId);
                if (!$schedule) {
                    continue;
                }

                // Exact balance for this schedule only; no partial allocation.
                $paymentAmount = $scheduleBalance;

                // Create repayment record
                $repaymentData = [
                    'type' => $paymentTypeCode,
                    'details' => 'Balance payment: ' . ($request->notes ?: 'Schedule balance cleared'),
                    'loan_id' => $request->loan_id,
                    'schedule_id' => $scheduleId,
                    'amount' => $paymentAmount,
                    'date_created' => now(),
                    'added_by' => auth()->id(),
                    'platform' => 'Web',
                ];

                // Handle mobile money vs cash/bank differently
                if ($paymentTypeCode == 2) {
                    // Mobile money - pending until callback confirms
                    $repaymentData['status'] = 0; // Pending
                    $repaymentData['txn_id'] = $flexipayReference;
                    $repaymentData['transaction_reference'] = $flexipaySecondaryReference ?: $flexipayReference;
                    $repaymentData['pay_status'] = 'PENDING';
                    $repaymentData['pay_message'] = 'Mobile money collection initiated';
                    $repaymentData['network'] = $network;
                    $repaymentData['payment_phone'] = $phoneNumber;

                    // Increment pending_count on the schedule
                    $schedule->increment('pending_count');

                } else {
                    // Cash or Bank Transfer - confirmed immediately
                    $paymentMethodName = $paymentTypeCode == 1 ? 'Cash' : 'Bank Transfer';
                    
                    // Auto-generate unique reference with appropriate prefix
                    // CH- for Cash, BT- for Bank Transfer
                    $prefix = $paymentTypeCode == 1 ? 'CH-' : 'BT-';
                    $microtime = (int)(microtime(true) * 1000); // milliseconds timestamp
                    $random = mt_rand(1000, 9999);
                    $reference = $prefix . $microtime . '-' . $random;
                    
                    // Verify uniqueness (should be extremely rare to have collision)
                    $attempts = 0;
                    while (DB::table('repayments')->where('transaction_reference', $reference)->exists() && $attempts < 5) {
                        $random = mt_rand(1000, 9999);
                        $reference = $prefix . $microtime . '-' . $random;
                        $attempts++;
                    }
                    
                    $repaymentData['status'] = 1; // Confirmed immediately
                    $repaymentData['payment_status'] = 'Confirmed';
                    $repaymentData['txn_id'] = $reference;
                    $repaymentData['transaction_reference'] = $reference;
                    $repaymentData['pay_status'] = 'SUCCESS';
                    $repaymentData['pay_message'] = $paymentMethodName . ' payment confirmed by ' . auth()->user()->name;
                }

                $repayment = Repayment::create($repaymentData);
                $paymentsCreated++;

                // For cash/bank, update schedule payment status immediately
                if ($paymentTypeCode != 2) {
                    // Payment amount = full outstanding balance (set by frontend readonly field).
                    // Always mark schedule fully paid and set date_cleared to freeze late-fee
                    // accrual — avoids residual balances when new periods start after payment.
                    $schedule->update([
                        'paid'         => $schedule->payment,
                        'status'       => 1,
                        'date_cleared' => now(),
                    ]);
                    $schedulesPaid[] = $scheduleData['due_date'];

                    $this->postGLEntry($repayment, $loan, ['loan_id' => $loan->id, 'schedule_id' => $scheduleId]);
                }
            }

            // For mobile money, create raw_payments record for tracking
            if ($paymentTypeCode == 2) {
                \App\Models\RawPayment::create([
                    'trans_id' => $flexipayReference,
                    'phone' => $phoneNumber,
                    'amount' => $request->amount,
                    'ref' => $flexipaySecondaryReference ?? '',
                    'message' => 'Balance payment initiated',
                    'status' => 'Processed',
                    'pay_status' => '00', // Pending
                    'pay_message' => 'Completed successfully',
                    'date_created' => now(),
                    'type' => 'repayment',
                    'direction' => 'cash_in',
                    'added_by' => auth()->id(),
                    'raw_message' => serialize(['loan_id' => $request->loan_id, 'payment_type' => 'balance_payment']),
                ]);
            }
            
            // Check if loan is fully paid after balance payment (only for cash/bank)
            if ($paymentTypeCode != 2) {
                $this->repaymentService->checkAndCloseLoanIfComplete($loan->id);
            }

            DB::commit();

            // Return appropriate message based on payment type
            if ($paymentTypeCode == 2) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mobile money collection initiated for ' . $paymentsCreated . ' schedule(s). Awaiting customer confirmation.',
                    'transaction_id' => $flexipayReference,
                    'payments_created' => $paymentsCreated,
                    'payment_type' => 'mobile_money'
                ]);
            } else {
                $message = "Successfully processed payment for {$paymentsCreated} schedule(s).";
                if (count($schedulesPaid) > 0) {
                    $message .= "<br>Schedules marked as paid: " . implode(', ', $schedulesPaid);
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'payments_created' => $paymentsCreated,
                    'schedules_paid' => count($schedulesPaid)
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Balance payment failed', [
                'loan_id' => $request->loan_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Balance payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process mobile money collection using FlexiPay
     */
    protected function processMobileMoneyCollection($loan, $amount, $phoneNumber, $network)
    {
        try {
            // Handle both PersonalLoan and GroupLoan
            if ($loan instanceof PersonalLoan) {
                $memberName = $loan->member->fname . ' ' . $loan->member->lname;
            } elseif ($loan instanceof GroupLoan) {
                $memberName = $loan->group->name ?? 'Group Loan';
            } else {
                // Fallback for old Loan model (if still used anywhere)
                $memberName = isset($loan->member) 
                    ? $loan->member->fname . ' ' . $loan->member->lname 
                    : 'Borrower';
            }
            
            // Use MobileMoneyService for collection (reverse of disbursement)
            $result = $this->mobileMoneyService->collectMoney(
                $memberName,
                $phoneNumber,
                $amount,
                "Loan repayment for {$loan->code}"
            );

            // Check if payment initiation was successful
            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Payment gateway error');
            }

            // Use Stanbic-generated reference (14 chars: EbP##########)
            // This is the same format used for all payment types
            $transactionRef = $result['reference'] ?? null;
            
            if (!$transactionRef) {
                throw new \Exception('Payment initiated but no transaction reference received');
            }

            return [
                'success' => true,
                'message' => $result['message'] ?? 'Mobile money collection processed',
                'reference' => $transactionRef,
                'flexipay_ref' => $result['flexipay_ref'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Mobile money collection error', [
                'loan_id' => $loan->id,
                'amount' => $amount,
                'phone' => $phoneNumber,
                'network' => $network,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Mobile money service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payment type code for database storage
     * Legacy system mapping: 1=cash, 2=mobile_money, 3=bank/cheque
     */
    protected function getPaymentTypeCode($paymentMethod)
    {
        $mapping = [
            'cash' => 1,
            'mobile_money' => 2,
            'bank_transfer' => 3,
            'cheque' => 3
        ];

        return $mapping[$paymentMethod] ?? 1;
    }

    /**
     * Display a listing of repayments
     */
    public function index(Request $request)
    {
        // Get loan type filter (school, student, staff, or default to personal/group)
        $loanType = $request->type ?? null;
        $perPage = 20;

        // Filter by loan type if specified (school, student, staff)
        if (in_array($loanType, ['school', 'student', 'staff'])) {
            // NOTE: Since school/student/staff loans are in separate tables (school_loans, student_loans, staff_loans)
            // and the current Repayment model references the old Loan model (personal_loans/group_loans),
            // we need to filter out ALL repayments when a school loan type is requested
            // until the repayment system is updated to support the new loan tables.
            
            // For now, return empty paginated result for school loan types
            $repayments = new \Illuminate\Pagination\LengthAwarePaginator(
                [],
                0,
                $perPage,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            $totals = [
                'total_amount' => 0,
                'total_principal' => 0,
                'total_interest' => 0,
                'total_fees' => 0,
                'total_penalty' => 0,
                'record_count' => 0,
                'average_payment' => 0,
            ];
            
            $branches = Branch::active()->get() ?? collect();
            $kpiMonth = $request->get('kpi_month');
            $kpiPeriodLabel = 'No supported repayment records';
            
            return view('admin.repayments.index', compact('repayments', 'branches', 'totals', 'loanType', 'kpiMonth', 'kpiPeriodLabel'))
                ->with('info', 'Repayment tracking for ' . ucfirst($loanType) . ' loans will be available once loans are disbursed and repayments begin. The system is ready to track repayments for school, student, and staff loans.');
        }

        // Default behavior: show successful personal repayments only.
        $query = Repayment::with(['loan.member', 'loan.product', 'loan.branch', 'addedBy'])
            ->where(function ($q) {
                $q->where('status', 1)
                    ->orWhere('payment_status', 'Completed');
            })
            ->where('amount', '>', 0);
        
        // Search functionality
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('loan', function ($loanQuery) use ($search) {
                    $loanQuery->where('code', 'like', "%{$search}%")
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery->where('fname', 'like', "%{$search}%")
                                ->orWhere('lname', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%")
                                ->orWhere('contact', 'like', "%{$search}%");
                        });
                });
            });
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('date_created', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('date_created', '<=', $request->end_date);
        }

        // Filter by payment method
        if ($request->filled('method')) {
            $query->where('type', $request->method);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('loan', function ($loanQuery) use ($request) {
                $loanQuery->where('branch_id', $request->branch_id);
            });
        }

        $branches = Branch::active()->get() ?? collect();

        // KPI totals (optionally by month)
        $kpiMonth = $request->get('kpi_month');
        $kpiStart = null;
        $kpiEnd = null;
        $kpiPeriodLabel = 'All matching repayments';
        if ($kpiMonth) {
            try {
                $kpiStart = Carbon::createFromFormat('Y-m', $kpiMonth)->startOfMonth();
                $kpiEnd = Carbon::createFromFormat('Y-m', $kpiMonth)->endOfMonth();
                $kpiPeriodLabel = $kpiStart->format('F Y');
            } catch (\Exception $e) {
                $kpiStart = null;
                $kpiEnd = null;
            }
        } elseif ($request->filled('start_date') || $request->filled('end_date')) {
            $from = $request->filled('start_date')
                ? Carbon::parse($request->start_date)->format('d M Y')
                : 'Beginning';
            $to = $request->filled('end_date')
                ? Carbon::parse($request->end_date)->format('d M Y')
                : 'Today';
            $kpiPeriodLabel = "{$from} to {$to}";
        }

        $feesQuery = Fee::paid()->where('amount', '>', 0);
        if ($kpiStart && $kpiEnd) {
            $feesQuery->whereBetween('datecreated', [$kpiStart, $kpiEnd]);
        } else {
            if ($request->filled('start_date')) {
                $feesQuery->whereDate('datecreated', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $feesQuery->whereDate('datecreated', '<=', $request->end_date);
            }
        }

        if ($request->filled('method')) {
            $feesQuery->where('payment_type', $request->method);
        }

        if ($request->filled('branch_id')) {
            $feesQuery->whereHas('loan', function ($loanQuery) use ($request) {
                $loanQuery->where('branch_id', $request->branch_id);
            });
        }

        if ($search !== '') {
            $feesQuery->where(function ($feeQuery) use ($search) {
                $feeQuery->where('pay_ref', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('loan', function ($loanQuery) use ($search) {
                        $loanQuery->where('code', 'like', "%{$search}%")
                            ->orWhereHas('member', function ($memberQuery) use ($search) {
                                $memberQuery->where('fname', 'like', "%{$search}%")
                                    ->orWhere('lname', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%")
                                    ->orWhere('contact', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhere('contact', 'like', "%{$search}%");
                    });
            });
        }

        $totals = $this->calculateRepaymentKpiTotalsFast($request, $kpiStart, $kpiEnd, (float) $feesQuery->sum('amount'));

        $repayments = (clone $query)
            ->orderBy('date_created', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.repayments.index', compact('repayments', 'branches', 'totals', 'loanType', 'kpiMonth', 'kpiPeriodLabel'));
    }

    /**
     * Show the form for creating a new repayment
     */
    public function create(Request $request)
    {
        // Get disbursed loans and filter by outstanding balance in PHP since balance is calculated
        $loans = Loan::with(['member', 'product', 'disbursements', 'repayments'])
                    ->where('status', 2) // Only disbursed loans
                    ->get()
                    ->filter(function($loan) {
                        return $loan->outstanding_balance > 0; // Use the calculated balance accessor
                    });

        // Pre-select loan if passed
        $selectedLoan = null;
        if ($request->has('loan_id')) {
            $selectedLoan = Loan::with(['member', 'product', 'schedules' => function($query) {
                $query->where('status', 0)->orderBy('payment_date');
            }])->find($request->loan_id);
        }

        return view('admin.repayments.create', compact('loans', 'selectedLoan'));
    }

    /**
     * Store a newly created repayment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|integer|in:1,2,3', // 1=cash, 2=mobile, 3=bank
            'txn_id' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'status' => 'nullable|boolean',
            'date' => 'nullable|date',
        ]);

        if (!$request->user()?->isSuperAdmin() && (int) $validated['type'] !== 2) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Access denied. Only the Super Administrator can confirm cash or bank repayments. Please use Mobile Money.');
        }

        $isMobileMoney = (int) $validated['type'] === 2;

        if ($isMobileMoney && $request->boolean('status')) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Mobile money repayments can only be completed after the gateway callback or status confirmation.');
        }

        $isConfirmed = !$isMobileMoney && $request->boolean('status');

        try {
            DB::beginTransaction();

            $loan = Loan::find($validated['loan_id']);
            
            // SECURITY: Prevent payments on stopped loans
            if ($loan && $loan->status == 6) {
                DB::rollBack();
                return redirect()->back()->withErrors([
                    'error' => 'This loan has been stopped and cannot receive payments. Please contact management for assistance.'
                ]);
            }

            // Prepare repayment data for old structure
            $repaymentData = [
                'type' => $validated['type'],
                'details' => $validated['details'] ?? 'Loan payment',
                'loan_id' => $validated['loan_id'],
                'schedule_id' => 0, // We'll update this if needed
                'amount' => $validated['amount'],
                'date_created' => $validated['date'] ?? now(),
                'added_by' => auth()->id(),
                'status' => $isConfirmed ? 1 : 0,
                'payment_status' => $isConfirmed ? 'Confirmed' : 'Pending',
                'platform' => 'Web',
                'txn_id' => $validated['txn_id'],
                'pay_status' => $isConfirmed ? 'SUCCESS' : 'PENDING',
                'pay_message' => $isConfirmed ? 'Payment confirmed' : 'Payment pending verification',
            ];

            $repayment = Repayment::create($repaymentData);

            // NOTE: personal_loans table doesn't have 'paid' column
            // Payment tracking is done via loan_schedules.paid only
            if ($isConfirmed) {
                // Check if loan is fully paid - validates ALL schedules before closing
                $this->repaymentService->checkAndCloseLoanIfComplete($validated['loan_id']);
                
                $loanForGL = PersonalLoan::with(['member', 'product'])->find($validated['loan_id']);
                if ($loanForGL && !$this->postGLEntry($repayment, $loanForGL)) {
                    session()->flash('warning', 'Repayment saved but GL journal entry could not be posted automatically. Please post manually via Journal Entries.');
                }
            }

            DB::commit();

            return redirect()->route('admin.repayments.show', $repayment)
                            ->with('success', 'Repayment recorded successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error recording repayment: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified repayment
     */
    public function show(Repayment $repayment)
    {
        $repayment->load([
            'loan.member',
            'loan.product',
            'loan.branch',
            'addedBy'
        ]);

        return view('admin.repayments.show', compact('repayment'));
    }

    /**
     * Show the form for editing the specified repayment
     */
    public function edit(Repayment $repayment)
    {
        // Only allow editing recent repayments (within 24 hours)
        if ($repayment->created_at->diffInHours(now()) > 24) {
            return redirect()->route('admin.repayments.show', $repayment)
                            ->with('error', 'Cannot edit repayments older than 24 hours.');
        }

        $repayment->load('loan.member');

        return view('admin.repayments.edit', compact('repayment'));
    }

    /**
     * Update the specified repayment
     */
    public function update(Request $request, Repayment $repayment)
    {
        // Only allow editing recent repayments
        if ($repayment->date_created && $repayment->date_created->diffInHours(now()) > 24) {
            return redirect()->route('admin.repayments.show', $repayment)
                            ->with('error', 'Cannot edit repayments older than 24 hours.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'type' => 'required|integer|in:1,2,3',
            'txn_id' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'status' => 'nullable|boolean',
            'date' => 'nullable|date',
        ]);

        if (!$request->user()?->isSuperAdmin() && (int) $validated['type'] !== 2) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Access denied. Only the Super Administrator can confirm cash or bank repayments. Please use Mobile Money.');
        }

        $isMobileMoney = (int) $validated['type'] === 2;

        if ($isMobileMoney && $request->boolean('status')) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Mobile money repayments can only be completed after the gateway callback or status confirmation.');
        }

        $isConfirmed = !$isMobileMoney && $request->boolean('status');

        try {
            DB::beginTransaction();

            $oldAmount = $repayment->amount;
            $wasConfirmed = $repayment->status == 1;

            // Reverse the old repayment effects if it was confirmed
            if ($wasConfirmed) {
                // No-op: payment tracking is schedule-based (loan_schedules.paid)
            }

            // Update repayment data
            $updateData = [
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'details' => $validated['details'],
                'txn_id' => $validated['txn_id'],
            ];

            if (!$isMobileMoney) {
                $updateData['status'] = $isConfirmed ? 1 : 0;
                $updateData['payment_status'] = $isConfirmed ? 'Confirmed' : 'Pending';
                $updateData['pay_status'] = $isConfirmed ? 'SUCCESS' : 'PENDING';
                $updateData['pay_message'] = $isConfirmed ? 'Payment confirmed' : 'Payment pending verification';
            }

            if (isset($validated['date'])) {
                $updateData['date_created'] = $validated['date'];
            }

            $repayment->update($updateData);

            // NOTE: personal_loans table doesn't have 'paid' column
            // Payment tracking is done via loan_schedules.paid only
            if ($isConfirmed) {
                // Check if loan is fully paid - validates ALL schedules before closing
                $this->repaymentService->checkAndCloseLoanIfComplete($repayment->loan_id);
            }

            DB::commit();

            return redirect()->route('admin.repayments.show', $repayment)
                            ->with('success', 'Repayment updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Error updating repayment: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified repayment
     */
    public function destroy(Repayment $repayment)
    {
        // Only allow deletion of recent repayments (within 24 hours)
        if ($repayment->date_created && $repayment->date_created->diffInHours(now()) > 24) {
            return redirect()->back()
                            ->with('error', 'Cannot delete repayments older than 24 hours.');
        }

        try {
            DB::beginTransaction();

            // Reverse the repayment effects if it was confirmed
            if ($repayment->status == 1) {
                // Keep rollback behavior on repayment record only.
                // Loan-level paid column is not a reliable source in current flow.
            }

            $repayment->delete();

            DB::commit();

            return redirect()->route('admin.repayments.index')
                            ->with('success', 'Repayment deleted successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                            ->with('error', 'Error deleting repayment: ' . $e->getMessage());
        }
    }

    /**
     * Calculate repayment breakdown
     */
    private function calculateRepaymentBreakdown(Loan $loan, $amount)
    {
        $breakdown = [
            'principal' => 0,
            'interest' => 0,
            'fees' => 0,
            'penalty' => 0
        ];

        // Get next due schedule
        $schedule = LoanSchedule::where('loan_id', $loan->id)
                               ->where('status', 0)
                               ->orderBy('payment_date')
                               ->first();

        if ($schedule) {
            $remaining = $amount;

            // First pay penalties and fees
            if ($schedule->penalty > 0) {
                $penaltyPayment = min($remaining, $schedule->penalty);
                $breakdown['penalty'] = $penaltyPayment;
                $remaining -= $penaltyPayment;
            }

            if ($schedule->fees > 0 && $remaining > 0) {
                $feesPayment = min($remaining, $schedule->fees);
                $breakdown['fees'] = $feesPayment;
                $remaining -= $feesPayment;
            }

            // Then pay interest
            if ($schedule->interest > 0 && $remaining > 0) {
                $interestPayment = min($remaining, $schedule->interest);
                $breakdown['interest'] = $interestPayment;
                $remaining -= $interestPayment;
            }

            // Finally pay principal
            if ($remaining > 0) {
                $breakdown['principal'] = $remaining;
            }
        } else {
            // If no schedule, assume it's all principal
            $breakdown['principal'] = $amount;
        }

        return $breakdown;
    }

    /**
     * Update loan schedules after repayment
     */
    private function updateLoanSchedules(Loan $loan, Repayment $repayment)
    {
        $schedules = LoanSchedule::where('loan_id', $loan->id)
                                ->where('status', 0)
                                ->orderBy('payment_date')
                                ->get();

        $remainingAmount = $repayment->amount;

        foreach ($schedules as $schedule) {
            if ($remainingAmount <= 0) break;

            $totalDue = $schedule->payment + $schedule->penalty + $schedule->fees;
            
            if ($remainingAmount >= $totalDue) {
                // Full payment for this schedule
                // CRITICAL: Only set date_cleared if TOTAL balance (including late fees) is zero
                $pendingLateFees = DB::table('late_fees')
                    ->where('schedule_id', $schedule->id)
                    ->where('status', 0)
                    ->sum('amount');
                
                $updateData = [
                    'status' => 1, // Paid
                    'paid_amount' => $totalDue,
                    'payment_date' => $repayment->date
                ];
                
                // Only freeze late fees if they're also paid
                if ($pendingLateFees <= 0.01) {
                    $updateData['date_cleared'] = now();
                }
                
                $schedule->update($updateData);
                $remainingAmount -= $totalDue;
            } else {
                // Partial payment
                $schedule->update([
                    'paid_amount' => $remainingAmount,
                    'status' => 2 // Partially paid
                ]);
                $remainingAmount = 0;
            }
        }
    }

    /**
     * Get loan details for AJAX
     */
    public function getLoanDetails(Loan $loan)
    {
        $loan->load(['member', 'product', 'schedules' => function($query) {
            $query->where('status', '!=', 1)->orderBy('payment_date'); // Not fully paid schedules
        }]);

        $nextDue = $loan->schedules->first();

        return response()->json([
            'success' => true,
            'loan' => [
                'id' => $loan->id,
                'code' => $loan->code,
                'member_name' => $loan->member->fname . ' ' . $loan->member->lname,
                'principal' => $loan->principal,
                'balance' => $loan->outstanding_balance ?? ($loan->principal - $loan->paid),
                'next_due_date' => $nextDue ? $nextDue->payment_date : null,
                'next_due_amount' => $nextDue ? $nextDue->payment : 0,
                'interest_due' => $nextDue ? $nextDue->interest : 0,
                'penalty' => $nextDue ? ($nextDue->penalty ?? 0) : 0,
                'fees' => $nextDue ? ($nextDue->fees ?? 0) : 0,
            ]
        ]);
    }

    /**
     * Generate repayment receipt
     */
    public function receipt(Repayment $repayment)
    {
        $repayment->load([
            'loan.member',
            'loan.product',
            'loan.branch',
            'addedBy',
            'schedule'
        ]);

        // Calculate payment breakdown if schedule exists
        $paymentBreakdown = null;
        if ($repayment->schedule_id && $repayment->schedule) {
            $schedule = $repayment->schedule;
            
            // CRITICAL: Calculate what was ACTUALLY paid, not what should be paid
            // Get the schedule's state to determine actual allocation
            $scheduleDue = $schedule->principal + $schedule->interest;
            $totalPaid = $repayment->amount;
            
            // Calculate late fees that existed at time of payment
            // Use current calculation as approximation (may be higher now if time passed)
            $lateFeeData = $this->repaymentService->calculateLateFee($schedule, $repayment->loan);
            $currentLateFee = $lateFeeData['net'];
            $totalWaivedAmount = $lateFeeData['waived'];
            $d = $lateFeeData['days_overdue'];
            $dd = $lateFeeData['periods_overdue'];
            
            // PAYMENT ALLOCATION - MUST MATCH store() method logic exactly
            // Order: Late Fees → Interest → Principal (BIMSADMIN waterfall method)
            // CRITICAL: Only allocate to late fees if payment EXCEEDS P+I
            $intrestamtpayable = $schedule->interest;
            $principalDue = $schedule->principal;
            
            // Check if payment exceeded P+I (proof that late fees were paid)
            if ($totalPaid > $scheduleDue) {
                // Payment EXCEEDS principal + interest, so excess goes to late fees
                $excessPayment = $totalPaid - $scheduleDue;
                $pafterlatepayment = min($excessPayment, $currentLateFee);
                $remainingForPI = $totalPaid - $pafterlatepayment;
            } else {
                // Payment does NOT exceed P+I, so NO late fees were paid
                $pafterlatepayment = 0;
                $remainingForPI = $totalPaid;
            }
            
            // Allocate remaining to Interest → Principal
            if ($remainingForPI >= $intrestamtpayable) {
                $pafterinterestpayment = $intrestamtpayable; // Full interest covered
                $pafterprinicpalpayment = min($remainingForPI - $intrestamtpayable, $principalDue);
            } else {
                $pafterinterestpayment = $remainingForPI; // Partial interest payment
                $pafterprinicpalpayment = 0;
            }
            
            $paymentBreakdown = [
                'late_fees_paid' => $pafterlatepayment,
                'interest_paid' => $pafterinterestpayment,
                'principal_paid' => $pafterprinicpalpayment,
                'schedule_principal' => $principalDue,
                'schedule_interest' => $intrestamtpayable,
                'late_fees_due' => $currentLateFee,
                'late_fees_waived' => $totalWaivedAmount,
                'days_late' => $d,
                'periods_overdue' => $dd
            ];
        }

        // Calculate loan summary with overpayment distribution (same as schedules page)
        if ($repayment->loan) {
            $loan = $repayment->loan;
            
            // Get total paid per schedule (single query)
            $paidPerSchedule = DB::table('repayments')
                ->whereIn('schedule_id', $loan->schedules->pluck('id')->toArray())
                ->where('status', 1)
                ->groupBy('schedule_id')
                ->select('schedule_id', DB::raw('SUM(amount) as total_paid'))
                ->pluck('total_paid', 'schedule_id')
                ->toArray();
            
            // Calculate outstanding with overpayment distribution
            $carryOverPaymentSummary = 0;
            $outstandingBalance = 0;
            
            foreach ($loan->schedules->sortBy('payment_date') as $schedule) {
                // Get amount paid from repayments table, fallback to schedule.paid
                $paidFromRepayments = floatval($paidPerSchedule[$schedule->id] ?? 0);
                $paidForSchedule = $paidFromRepayments > 0 ? $paidFromRepayments : floatval($schedule->paid ?? 0);
                
                // Apply carry-over from previous overpaid schedules
                $paidForSchedule += $carryOverPaymentSummary;
                $carryOverPaymentSummary = 0;
                
                // Calculate late fees using helper method
                $lateFeeData = $this->repaymentService->calculateLateFee($schedule, $loan);
                $latepayment = $lateFeeData['net'];
                
                // Calculate schedule balance
                $scheduleDue = $schedule->principal + $schedule->interest + $latepayment;
                $scheduleBalance = $scheduleDue - $paidForSchedule;
                
                // Capture overpayment as carry-over
                if ($scheduleBalance < 0) {
                    $carryOverPaymentSummary = abs($scheduleBalance);
                    $scheduleBalance = 0;
                }
                
                $outstandingBalance += $scheduleBalance;
            }
            
            // Calculate total paid
            $totalPaid = Repayment::where('loan_id', $repayment->loan_id)
                ->where(function($q) {
                    $q->where('status', 1)
                      ->orWhere('payment_status', 'Completed');
                })
                ->sum('amount');
            
            $totalDue = $loan->principal + $loan->interest;
            
            // Add to loan object for view
            $repayment->loan->paid = $totalPaid;
            $repayment->loan->outstanding_balance = $outstandingBalance; // Uses distribution logic
            $repayment->loan->total_due = $totalDue;
            $repayment->loan->unused_overpayment = $carryOverPaymentSummary; // Available for refund
        }

        return view('admin.repayments.receipt', compact('repayment', 'paymentBreakdown'));
    }

    /**
     * Get pending transaction for a schedule (for Check Progress button)
     */
    public function getPendingTransaction($scheduleId)
    {
        try {
            // Find the most recent pending repayment for this schedule
            $repayment = Repayment::where('schedule_id', $scheduleId)
                ->where('status', 0) // Pending
                ->orderBy('id', 'desc')
                ->first();
            
            if ($repayment && $repayment->txn_id) {
                return response()->json([
                    'success' => true,
                    'transaction_id' => $repayment->txn_id
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'No pending payment found for this schedule'
            ]);
        } catch (\Exception $e) {
            \Log::error('Get pending transaction error', [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving pending transaction'
            ], 500);
        }
    }

    /**
     * Check mobile money payment status (for 60-second polling)
     */
    public function checkPaymentStatus($transactionId)
    {
        try {
            // Check raw_payments table for transaction status
            $rawPayment = RawPayment::where('trans_id', $transactionId)->first();
            
            if (!$rawPayment) {
                return response()->json([
                    'status' => 'UNKNOWN',
                    'message' => 'Transaction not found'
                ]);
            }

            // Check if payment status has been updated by CheckTransactions cron
            // pay_status codes: '00' = pending, '01' = success, '02'/'03' = failed
            if ($rawPayment->pay_status === '01') {
                // Payment successful - update repayment and schedule
                $repayment = Repayment::where('txn_id', $transactionId)->first();
                
                if ($repayment && $repayment->status == 0) {
                    DB::beginTransaction();
                    try {
                        // Update repayment status
                        $repayment->update([
                            'status' => 1, // Confirmed
                            'payment_status' => 'Completed',
                            'pay_status' => 'SUCCESS',
                            'pay_message' => 'Payment confirmed via mobile money'
                        ]);
                        
                        // Get loan and schedule
                        $loanResult = $this->findLoanById($repayment->loan_id);
                        $loan = $loanResult['loan'];
                        $schedule = LoanSchedule::find($repayment->schedule_id);
                        
                        if ($loan && $schedule) {
                            // NOTE: personal_loans table doesn't have 'paid' column
                            // Payment tracking is done via loan_schedules.paid only
                            
                            // Handle payment allocation with overpayment redistribution
                            $paymentAmount = $repayment->amount;
                            $schedule->increment('paid', $paymentAmount);
                            
                            // Decrement pending_count
                            if ($schedule->pending_count > 0) {
                                $schedule->decrement('pending_count');
                            }
                            
                            // Check if there's an overpayment on this schedule.
                            // Calculate dynamic late fees so the late-fee portion of a payment
                            // is not mistakenly forwarded to the next schedule.
                            if (method_exists($loan, 'loadMissing')) {
                                $loan->loadMissing('product');
                            }
                            $cbLateFeeData    = $this->repaymentService->calculateLateFee($schedule, $loan);
                            $cbLateFees       = $cbLateFeeData['net'];
                            $cbTotalScheduleDue = $schedule->payment + $cbLateFees;

                            if ($schedule->paid >= $cbTotalScheduleDue) {
                                // P+I AND late fees fully paid
                                $overpayment = $schedule->paid - $cbTotalScheduleDue;

                                $schedule->update([
                                    'paid'         => $schedule->payment,
                                    'status'       => 1,
                                    'date_cleared' => now(),
                                ]);

                                // Apply only the TRUE excess (above P+I + late fees) to next schedule
                                if ($overpayment > 1) {
                                    $nextSchedule = \App\Models\LoanSchedule::where('loan_id', $loan->id)
                                        ->where('status', '!=', 1)
                                        ->where('id', '>', $schedule->id)
                                        ->orderBy('id')
                                        ->first();
                                    
                                    if ($nextSchedule) {
                                        $nextSchedule->increment('paid', $overpayment);
                                        
                                        // Check if next schedule is now fully paid (including late fees)
                                        $nextLateFees = DB::table('late_fees')
                                            ->where('schedule_id', $nextSchedule->id)
                                            ->where('status', 0)
                                            ->sum('amount');
                                        $nextTotalDue = $nextSchedule->payment + $nextLateFees;
                                        
                                        if ($nextSchedule->paid >= $nextTotalDue) {
                                            $cbNextUpdateData = ['status' => 1];
                                            if ($nextLateFees <= 0.01) {
                                                $cbNextUpdateData['date_cleared'] = now();
                                            }
                                            $nextSchedule->update($cbNextUpdateData);
                                        }
                                    }
                                }
                            } elseif ($schedule->paid >= $schedule->payment && $cbLateFees > 0.01) {
                                // P+I paid but late fees still outstanding
                                $schedule->update([
                                    'paid'   => $schedule->payment,
                                    'status' => 1,
                                    // No date_cleared: late fees still unpaid
                                ]);
                            } elseif ($schedule->paid >= $schedule->payment) {
                                // P+I paid, no late fees — fully settled
                                $schedule->update([
                                    'paid'         => $schedule->payment,
                                    'status'       => 1,
                                    'date_cleared' => now(),
                                ]);
                            }
                            
                            // Check if loan is fully paid by checking ALL schedules
                            $this->repaymentService->checkAndCloseLoanIfComplete($loan->id);
                        }
                        
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        \Log::error('Payment status update failed', [
                            'transaction_id' => $transactionId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                return response()->json([
                    'status' => 'SUCCESS',
                    'message' => 'Payment completed successfully'
                ]);
            } elseif (in_array($rawPayment->pay_status, ['02', '03', 'FAILED'])) {
                return response()->json([
                    'status' => 'FAILED',
                    'message' => $rawPayment->pay_message ?? 'Payment failed'
                ]);
            } else {
                // Still pending
                return response()->json([
                    'status' => 'PENDING',
                    'message' => 'Payment is being processed'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Check payment status error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'ERROR',
                'message' => 'Error checking payment status'
            ], 500);
        }
    }

    /**
     * Store repayment with mobile money collection (Improved Flow)
     */
    public function storeMobileMoneyRepayment(Request $request)
    {
        $validated = $request->validate([
            'loan_id' => 'required|exists:personal_loans,id',
            's_id' => 'nullable|exists:loan_schedules,id', // Made nullable for old loans
            'amount' => 'required|numeric|min:0.01',
            'details' => 'required|string|max:1000', // Increased max, will be truncated if needed
            'member_phone' => 'required|string',
            'member_name' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $loan = Loan::findOrFail($validated['loan_id']);
            
            // SECURITY: Prevent payments on stopped loans
            if ($loan->status == 6) {
                DB::rollBack();
                
                \Log::warning('Attempted mobile money payment on stopped loan', [
                    'loan_id' => $loan->id,
                    'user_id' => auth()->id()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'This loan has been stopped and cannot receive payments. Please contact management for assistance.'
                ], 403);
            }
            
            // Auto-find schedule if not provided (for old loans)
            if (!$validated['s_id']) {
                $schedule = LoanSchedule::where('loan_id', $validated['loan_id'])
                    ->where('status', 0) // Pending
                    ->orderBy('id')
                    ->first();
                
                if (!$schedule) {
                    throw new \Exception('No pending payment schedules found for this loan.');
                }
                
                $validated['s_id'] = $schedule->id;
            } else {
                $schedule = LoanSchedule::findOrFail($validated['s_id']);
            }

            // SEQUENTIAL PAYMENT ENFORCEMENT
            $sequenceCheck = $this->checkEarlierSchedulesPaid($schedule->id, $validated['loan_id']);
            if (!$sequenceCheck['allowed']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $sequenceCheck['message']
                ], 422);
            }

            // EXACT AMOUNT VALIDATION: payment must equal total balance (P+I+late fees - already paid)
            $scheduleDue = $schedule->principal + $schedule->interest;
            $alreadyPaid = floatval(
                DB::table('repayments')
                    ->where('schedule_id', $schedule->id)
                    ->where('amount', '>', 0)
                    ->whereNotIn('status', [-1, 2])
                    ->where(function ($q) {
                        $q->where('status', 1)->orWhere('payment_status', 'Completed');
                    })
                    ->sum('amount')
            );
            $loan->loadMissing('product');
            $lateFeeData = $this->repaymentService->calculateLateFee($schedule, $loan);
            $totalOwed = max(0, ($scheduleDue + $lateFeeData['net']) - $alreadyPaid);

            if ($validated['amount'] > ($totalOwed + 1)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => sprintf(
                        'Excess payments not allowed. Payment amount (UGX %s) exceeds what is owed (UGX %s). Please pay the exact amount.',
                        number_format($validated['amount'], 0),
                        number_format($totalOwed, 0)
                    ),
                    'max_amount' => $totalOwed
                ], 422);
            }

            if ($validated['amount'] < ($totalOwed - 1)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => sprintf(
                        'Partial payments not allowed. Payment amount (UGX %s) is less than what is owed (UGX %s). Please pay the exact amount.',
                        number_format($validated['amount'], 0),
                        number_format($totalOwed, 0)
                    ),
                    'required_amount' => $totalOwed
                ], 422);
            }

            // Create repayment record with pending status
            // Truncate details to reasonable length (keep it concise for SMS/display purposes)
            $shortDetails = mb_substr($validated['details'], 0, 200);
            if (mb_strlen($validated['details']) > 200) {
                $shortDetails .= '...';
            }
            
            $repayment = Repayment::create([
                'type' => 2, // Mobile Money
                'details' => $shortDetails,
                'loan_id' => $validated['loan_id'],
                'schedule_id' => $validated['s_id'],
                'amount' => $validated['amount'],
                'payment_phone' => $validated['member_phone'],
                'payment_status' => 'Pending',
                'date_created' => now(),
                'added_by' => auth()->id(),
                'platform' => 'Web',
                'medium' => null, // Will be set after payment confirmation
            ]);

            // Initialize Mobile Money Service
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);

            // Collect money from member's phone (Stanbic will generate short request ID)
            $result = $mobileMoneyService->collectMoney(
                $validated['member_name'],
                $validated['member_phone'],
                $validated['amount'],
                "Loan Repayment: Schedule #" . $schedule->id
            );

            // Check if payment initiation was successful
            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Payment gateway error');
            }

            // Use Stanbic-generated reference (14 chars: EbP##########)
            // This is the same format used for all payment types
            $transactionRef = $result['reference'] ?? null;
            
            if (!$transactionRef) {
                throw new \Exception('Payment initiated but no transaction reference received');
            }
            
            $repayment->update([
                'payment_raw' => json_encode($result),
                'txn_id' => $transactionRef, // FlexiPay-generated reference
                'transaction_reference' => $transactionRef // Set both fields for consistency
            ]);

            // Increment pending_count on the schedule (for UI display)
            $schedule->increment('pending_count');

            DB::commit();

            // Return success with transaction reference for polling
            return response()->json([
                'success' => true,
                'message' => 'Payment request sent to member\'s phone',
                'transaction_reference' => $transactionRef,
                'repayment_id' => $repayment->id,
                'status_code' => $result['status_code'] ?? 'PENDING'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error("Mobile Money Repayment Error", [
                'loan_id' => $validated['loan_id'] ?? null,
                'schedule_id' => $validated['s_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check mobile money payment status for repayments with late fee calculation
     */
    public function checkRepaymentMmStatus($transactionRef)
    {
        try {
            \Log::info("=== CHECKING REPAYMENT MOBILE MONEY STATUS ===", [
                'transaction_ref' => $transactionRef
            ]);
            
            // Find ALL repayments by transaction reference (balance payments can have multiple)
            $repayments = Repayment::where('transaction_reference', $transactionRef)
                ->orWhere('txn_id', $transactionRef)
                ->get();
            
            if ($repayments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Repayment not found'
                ], 404);
            }

            // If already completed, return success
            $firstRepayment = $repayments->first();
            if ($firstRepayment->payment_status === 'Completed' || $firstRepayment->status == 1) {
                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed successfully',
                    'repayment_id' => $firstRepayment->id,
                    'receipt_url' => route('admin.repayments.receipt', $firstRepayment->id)
                ]);
            }

            // Always initialize service — it is needed for the status check regardless of phone availability
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);

            // Detect network from payment phone for Stanbic status check
            $network = null;
            if ($firstRepayment->payment_phone) {
                $network = $mobileMoneyService->detectNetwork($firstRepayment->payment_phone);
                
                \Log::info("Network detected from payment phone", [
                    'phone' => $firstRepayment->payment_phone,
                    'network' => $network
                ]);
            }

            // Check status with FlexiPay (will auto-select Stanbic or Emuria)
            $statusResult = $mobileMoneyService->checkTransactionStatus($transactionRef, $network);

            \Log::info("FlexiPay Repayment Status Result", [
                'transaction_ref' => $transactionRef,
                'status' => $statusResult['status'] ?? 'unknown',
                'full_result' => $statusResult,
                'repayments_count' => $repayments->count()
            ]);

            // Update repayment based on status
            if ($statusResult['status'] === 'completed') {
                // Delegate to RepaymentService::approveRepayment() — the same path the callback uses.
                // This ensures schedule updates, late-fee calculation, loan-close check and GL
                // journal posting are all handled consistently in one place.
                $lastApproved = null;
                foreach ($repayments as $repayment) {
                    $result = $this->repaymentService->approveRepayment(
                        $repayment->id,
                        '00',
                        'Payment confirmed via status check'
                    );
                    if ($result['success']) {
                        $lastApproved = $repayment;
                    }
                    // Decrement pending_count (approveRepayment doesn't touch it)
                    $schedule = LoanSchedule::find($repayment->schedule_id);
                    if ($schedule && $schedule->pending_count > 0) {
                        $schedule->decrement('pending_count');
                    }
                }

                $receiptRepayment = $lastApproved ?? $repayments->first();

                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed successfully',
                    'repayment_id' => $receiptRepayment->id,
                    'receipt_url' => route('admin.repayments.receipt', $receiptRepayment->id)
                ]);
                
            } elseif ($statusResult['status'] === 'failed') {
                // Check if payment is recent (within 2 minutes) - FlexiPay retries 3 times
                $createdAt = \Carbon\Carbon::parse($firstRepayment->date_created);
                $ageInMinutes = $createdAt->diffInMinutes(now());
                
                if ($ageInMinutes < 2) {
                    // Payment is recent - don't mark as failed yet
                    \Log::info("Repayment marked as pending - still within retry window", [
                        'repayment_count' => $repayments->count(),
                        'age_minutes' => $ageInMinutes,
                        'transaction_ref' => $transactionRef
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'status' => 'pending',
                        'message' => 'Payment being processed - FlexiPay will retry if user cancelled'
                    ]);
                }
                
                // Payment is old enough - mark all as failed
                foreach ($repayments as $repayment) {
                    $repayment->update([
                        'payment_status' => 'Failed',
                        'payment_raw' => json_encode($statusResult)
                    ]);

                    // Decrement pending_count on the schedule since payment failed
                    $schedule = LoanSchedule::find($repayment->schedule_id);
                    if ($schedule && $schedule->pending_count > 0) {
                        $schedule->decrement('pending_count');
                    }
                }

                return response()->json([
                    'success' => true,
                    'status' => 'failed',
                    'message' => $statusResult['message'] ?? 'Payment failed'
                ]);
            }

            // Still pending
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment pending - waiting for member authorization'
            ]);

        } catch (\Exception $e) {
            \Log::error("Repayment Mobile Money Status Check Error", [
                'transaction_ref' => $transactionRef,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Status check failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry a failed mobile money repayment
     */
    public function retryMobileMoneyRepayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'repayment_id' => 'required|exists:repayments,id',
                'member_phone' => 'required|string',
                'member_name' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'details' => 'required|string'
            ]);

            // Find the repayment
            $repayment = Repayment::findOrFail($validated['repayment_id']);

            // Verify the repayment is failed/pending and is mobile money
            if ($repayment->type != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only mobile money payments can be retried'
                ], 400);
            }

            if ($repayment->payment_status === 'Completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment has already been completed'
                ], 400);
            }

            // Store original amount if this is first retry and amount changed
            if (empty($repayment->original_amount) && $repayment->amount != $validated['amount']) {
                $originalAmount = $repayment->amount;
            } else {
                $originalAmount = $repayment->original_amount;
            }

            // Reset repayment to pending status
            $repayment->update([
                'payment_status' => 'Pending',
                'details' => $validated['details'],
                'payment_phone' => $validated['member_phone'],
                'amount' => $validated['amount'],
                'original_amount' => $originalAmount,
                'date_created' => now() // Reset timestamp for 2-minute grace period
            ]);

            // Initialize Mobile Money Service
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);

            // Retry collection (Stanbic will generate new short request ID)
            $result = $mobileMoneyService->collectMoney(
                $validated['member_name'],
                $validated['member_phone'],
                $validated['amount'],
                $validated['details']
            );

            // Check if payment initiation was successful
            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Payment gateway error');
            }

            // Use Stanbic-generated reference (14 chars: EbP##########)
            // This is the same format used for all payment types
            $transactionRef = $result['reference'] ?? null;
            
            if (!$transactionRef) {
                throw new \Exception('Payment initiated but no transaction reference received');
            }
            
            $repayment->update([
                'transaction_reference' => $transactionRef,
                'payment_raw' => json_encode($result)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment retry request sent to member\'s phone',
                'transaction_reference' => $transactionRef,
                'repayment_id' => $repayment->id
            ]);

        } catch (\Exception $e) {
            \Log::error("Repayment Mobile Money Retry Error", [
                'repayment_id' => $validated['repayment_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retry payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get repayment details for retry modal
     */
    public function getRepayment($id)
    {
        try {
            $repayment = Repayment::with(['loan', 'schedule'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'repayment' => [
                    'id' => $repayment->id,
                    'amount' => $repayment->amount,
                    'payment_phone' => $repayment->payment_phone,
                    'details' => $repayment->details,
                    'payment_status' => $repayment->payment_status,
                    'type' => $repayment->type,
                    'original_amount' => $repayment->original_amount,
                    'transaction_reference' => $repayment->transaction_reference
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error("Get Repayment Error", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load repayment details'
            ], 500);
        }
    }

    /**
     * Get pending mobile money repayments for a schedule
     */
    public function getSchedulePendingRepayments($scheduleId)
    {
        try {
            // Find pending mobile money repayments regardless of which path created them.
            // Old path (storeRepayment):          status=0, pay_status='PENDING'
            // New path (storeMobileMoneyRepayment): payment_status='Pending'
            $pendingRepayments = Repayment::where('schedule_id', $scheduleId)
                ->where('type', 2) // Mobile Money type
                ->where(function ($q) {
                    $q->where('payment_status', 'Pending')
                      ->orWhere(function ($q2) {
                          $q2->where('status', 0)->where('pay_status', 'PENDING');
                      });
                })
                ->orderBy('date_created', 'desc')
                ->get(['id', 'amount', 'payment_phone', 'transaction_reference', 'txn_id', 'date_created'])
                ->map(function ($r) {
                    // Normalise reference so the UI always has a non-null value to poll with
                    if (empty($r->transaction_reference) && !empty($r->txn_id)) {
                        $r->transaction_reference = $r->txn_id;
                    }
                    return $r;
                });

            return response()->json([
                'success' => true,
                'pending_repayments' => $pendingRepayments
            ]);

        } catch (\Exception $e) {
            \Log::error("Get Schedule Pending Repayments Error", [
                'schedule_id' => $scheduleId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load pending repayments'
            ], 500);
        }
    }

    /**
     * Stop a loan (mark as stopped - status 6)
     * Only Super Administrator can stop loans
     */
    public function stopLoan(Request $request, $loanId)
    {
        try {
            // Check authorization - only superadmin
            $user = auth()->user();
            if (!$user?->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only the Super Administrator can stop loans.'
                ], 403);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|min:10|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            DB::beginTransaction();

            // Find the loan (try both personal and group loans)
            $loan = PersonalLoan::find($loanId);
            $loanType = 'personal';
            
            if (!$loan) {
                $loan = GroupLoan::find($loanId);
                $loanType = 'group';
            }

            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ], 404);
            }

            // Check if loan is active (status 2 = Disbursed)
            if ($loan->status != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active/disbursed loans can be stopped'
                ], 422);
            }

            // Update loan status to 6 (Stopped)
            $loan->status = 6;
            $loan->save();

            // Log the action
            \Log::info("Loan Stopped", [
                'loan_id' => $loanId,
                'loan_type' => $loanType,
                'loan_code' => $loan->code,
                'stopped_by' => auth()->user()->name ?? 'System',
                'reason' => $request->input('reason'),
                'date' => now()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan stopped successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error("Stop Loan Error", [
                'loan_id' => $loanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to stop loan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reschedule loan payments by postponing all unpaid schedules
     */
    public function rescheduleLoan(Request $request, $loanId)
    {
        try {
            // Check if auto-calculate days
            $action = $request->input('action', 'custom_days');
            $days = $request->input('days');
            
            // Validate input
            $validationRules = [
                'reason' => 'required|string|min:10|max:1000',
                'waive_fees' => 'required|in:0,1'
            ];
            
            if ($action === 'custom_days') {
                $validationRules['days'] = 'required|integer|min:1|max:365';
            }
            
            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            DB::beginTransaction();

            // Find the loan from either personal_loans or group_loans
            $loan = PersonalLoan::find($loanId);
            $loanType = 'personal';
            
            if (!$loan) {
                $loan = GroupLoan::find($loanId);
                $loanType = 'group';
            }

            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ], 404);
            }

            // Check if loan is active (status 2 = Disbursed)
            if ($loan->status != 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only active/disbursed loans can be rescheduled'
                ], 400);
            }

            $reason = $request->input('reason');
            $waiveFees = $request->input('waive_fees') == '1';

            if ($waiveFees && !auth()->user()?->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only the Super Administrator can waive late fees during rescheduling.'
                ], 403);
            }

            // Get all unpaid schedules for this loan
            $unpaidSchedules = LoanSchedule::where('loan_id', $loanId)
                ->where('status', 0) // Unpaid
                ->orderBy('payment_date', 'asc')
                ->get();

            if ($unpaidSchedules->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No unpaid schedules found for this loan'
                ], 400);
            }

            $updatedSchedules = 0;
            $daysToPostpone = 0;
            
            // Determine rescheduling strategy
            if ($action === 'start_today' || $days === 'auto') {
                // Calculate days to shift so first unpaid schedule starts today
                $today = Carbon::today();
                $firstSchedule = $unpaidSchedules->first();
                
                // Parse date - handle both d-m-Y and Y-m-d formats
                try {
                    $firstScheduleDate = \Carbon\Carbon::createFromFormat('d-m-Y', $firstSchedule->payment_date);
                } catch (\Exception $e) {
                    try {
                        $firstScheduleDate = \Carbon\Carbon::createFromFormat('Y-m-d', $firstSchedule->payment_date);
                    } catch (\Exception $e2) {
                        $firstScheduleDate = \Carbon\Carbon::parse($firstSchedule->payment_date);
                    }
                }
                
                // Calculate the shift needed
                $daysToShift = $firstScheduleDate->diffInDays($today, false);
                
                // If first schedule is in the past, shift forward to today
                if ($daysToShift < 0) {
                    $daysToShift = abs($daysToShift);
                } else {
                    // First schedule is today or future, no shift needed
                    $daysToShift = 0;
                }
                
                $daysToPostpone = $daysToShift;
                
                // Shift all unpaid schedules by the same number of days
                foreach ($unpaidSchedules as $schedule) {
                    // Parse date - handle both d-m-Y and Y-m-d formats
                    try {
                        $currentDate = \Carbon\Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
                    } catch (\Exception $e) {
                        try {
                            $currentDate = \Carbon\Carbon::createFromFormat('Y-m-d', $schedule->payment_date);
                        } catch (\Exception $e2) {
                            $currentDate = \Carbon\Carbon::parse($schedule->payment_date);
                        }
                    }
                    $newDate = $currentDate->addDays($daysToShift);
                    
                    $schedule->payment_date = $newDate->format('d-m-Y');
                    
                    // Optionally waive late fees by clearing date_cleared
                    // This resets the arrears calculation to use current date instead of old cleared date
                    if ($waiveFees) {
                        $schedule->date_cleared = null;
                        if (isset($schedule->late_fee)) {
                            $schedule->late_fee = 0;
                        }
                    }
                    
                    $schedule->save();
                    $updatedSchedules++;
                }
            } else {
                // Custom days postponement
                $daysToPostpone = (int) $days;
                
                // Update all unpaid schedules by adding the specified days
                foreach ($unpaidSchedules as $schedule) {
                    // Parse date - handle both d-m-Y and Y-m-d formats
                    try {
                        $currentDate = \Carbon\Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
                    } catch (\Exception $e) {
                        try {
                            $currentDate = \Carbon\Carbon::createFromFormat('Y-m-d', $schedule->payment_date);
                        } catch (\Exception $e2) {
                            $currentDate = \Carbon\Carbon::parse($schedule->payment_date);
                        }
                    }
                    $newDate = $currentDate->addDays($daysToPostpone);
                    
                    $schedule->payment_date = $newDate->format('d-m-Y');
                    
                    // Optionally waive late fees by clearing date_cleared
                    // This resets the arrears calculation to use current date instead of old cleared date
                    if ($waiveFees) {
                        $schedule->date_cleared = null;
                        if (isset($schedule->late_fee)) {
                            $schedule->late_fee = 0;
                        }
                    }
                    
                    $schedule->save();
                    $updatedSchedules++;
                }
            }

            // Log the reschedule action in a notes/audit field if available
            $logMessage = sprintf(
                "[%s] Loan rescheduled: %d schedules postponed by %d days. Reason: %s. Late fees waived: %s. By: %s",
                now()->format('Y-m-d H:i:s'),
                $updatedSchedules,
                $daysToPostpone,
                $reason,
                $waiveFees ? 'Yes' : 'No',
                auth()->user()->name ?? 'System'
            );

            // Add to loan notes if column exists
            if (\Schema::hasColumn($loanType === 'personal' ? 'personal_loans' : 'group_loans', 'notes')) {
                $existingNotes = $loan->notes ?? '';
                $loan->notes = $existingNotes . "\n" . $logMessage;
                $loan->save();
            }

            // Also log to Laravel log for audit trail
            Log::info("Loan Rescheduled", [
                'loan_id' => $loanId,
                'loan_type' => $loanType,
                'loan_code' => $loan->code,
                'days_postponed' => $daysToPostpone,
                'schedules_updated' => $updatedSchedules,
                'reason' => $reason,
                'waive_fees' => $waiveFees,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'Unknown'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => sprintf(
                    'Successfully rescheduled %d payment(s) by %d days',
                    $updatedSchedules,
                    $daysToPostpone
                ),
                'data' => [
                    'schedules_updated' => $updatedSchedules,
                    'days_postponed' => $daysToPostpone,
                    'fees_waived' => $waiveFees
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Loan Reschedule Error", [
                'loan_id' => $loanId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reschedule loan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Waive selected late fees for a loan
     * Only accessible by the superadmin role
     */
    public function waiveLateFees(Request $request)
    {
        try {
            // Authorization check - only superadmin can waive late fees
            $user = auth()->user();

            if (!$user?->isSuperAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only the Super Administrator can waive late fees.'
                ], 403);
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'loan_id' => 'required|integer',
                'loan_type' => 'nullable|in:personal,group',
                'late_fees' => 'required|json',
                'waiver_reason' => 'required|string|max:255'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . $validator->errors()->first()
                ], 422);
            }
            
            $loanId = $request->loan_id;
            $loanType = $request->input('loan_type');
            $lateFees = json_decode($request->late_fees, true);
            $waiverReason = $request->waiver_reason;
            
            if (empty($lateFees)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No late fees selected'
                ], 422);
            }
            
            // Get loan and member details. loan_type is optional for legacy callers.
            $loan = match ($loanType) {
                'personal' => PersonalLoan::find($loanId),
                'group' => GroupLoan::find($loanId),
                default => PersonalLoan::find($loanId) ?: GroupLoan::find($loanId),
            };

            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ], 404);
            }
            
            $memberId = $loan->member_id ?? $loan->group_id ?? 0;
            
            DB::beginTransaction();
            
            $waivedCount = 0;
            $totalWaived = 0;
            $failedIds = [];
            
            foreach ($lateFees as $lateFee) {
                // Check if this is an existing late_fee record or a calculated penalty from schedule
                if (isset($lateFee['late_fee_id'])) {
                    // Existing late fee record in late_fees table
                    $lateFeeId = $lateFee['late_fee_id'];
                    $amount = $lateFee['amount'];
                    
                    // Verify the late fee exists and is pending
                    $lateFeeRecord = DB::table('late_fees')
                        ->where('id', $lateFeeId)
                        ->where('loan_id', $loanId)
                        ->where('status', 0) // Only pending late fees can be waived
                        ->first();
                    
                    if (!$lateFeeRecord) {
                        $failedIds[] = $lateFeeId;
                        continue;
                    }
                    
                    // Update late fee status to waived (status = 2)
                    $updated = DB::table('late_fees')
                        ->where('id', $lateFeeId)
                        ->update([
                            'status' => 2, // 2 = waived
                            'waiver_reason' => $waiverReason,
                            'waived_at' => now(),
                            'waived_by' => $user->id,
                            'updated_at' => now()
                        ]);
                    
                    if ($updated) {
                        $waivedCount++;
                        $totalWaived += $lateFeeRecord->amount;
                    } else {
                        $failedIds[] = $lateFeeId;
                    }
                    
                } else if (isset($lateFee['schedule_id'])) {
                    // Calculated penalty from schedule - create late_fee record and mark as waived
                    $scheduleId = $lateFee['schedule_id'];
                    $amount = $lateFee['amount'];
                    
                    // Get schedule details
                    $schedule = $loan instanceof GroupLoan
                        ? GroupLoanSchedule::find($scheduleId)
                        : LoanSchedule::find($scheduleId);

                    if (!$schedule || $schedule->loan_id != $loanId) {
                        $failedIds[] = $scheduleId;
                        continue;
                    }
                    
                    // Calculate days overdue
                    $dueDate = Carbon::parse($schedule->payment_date);
                    $daysOverdue = $dueDate->isPast() ? $dueDate->diffInDays(now(), false) : 0;
                    
                    // Insert late fee record as waived
                    $inserted = DB::table('late_fees')->insert([
                        'loan_id' => $loanId,
                        'schedule_id' => $scheduleId,
                        'member_id' => $memberId,
                        'amount' => $amount,
                        'days_overdue' => $daysOverdue,
                        'schedule_due_date' => $schedule->payment_date,
                        'calculated_date' => now(),
                        'status' => 2, // 2 = waived (create as already waived)
                        'waiver_reason' => $waiverReason,
                        'waived_at' => now(),
                        'waived_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    if ($inserted) {
                        $waivedCount++;
                        $totalWaived += $amount;
                    } else {
                        $failedIds[] = $scheduleId;
                    }
                }
            }
            
            // Log the waiver action
            Log::info("Late fees waived", [
                'loan_id' => $loanId,
                'waived_by' => $user->id,
                'waived_by_name' => $user->name ?? $user->fname . ' ' . $user->lname,
                'count' => $waivedCount,
                'total_amount' => $totalWaived,
                'reason' => $waiverReason,
                'failed_ids' => $failedIds
            ]);
            
            if ($waivedCount > 0) {
                DB::commit();
                
                $message = "Successfully waived <strong>{$waivedCount} late fee(s)</strong> totaling <strong>UGX " . 
                          number_format($totalWaived, 0) . "</strong>";
                
                if (!empty($failedIds)) {
                    $message .= "<br><small class='text-warning'>Note: " . count($failedIds) . " late fee(s) could not be waived (may have been already paid/waived)</small>";
                }
                
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'waived_count' => $waivedCount,
                    'total_waived' => $totalWaived
                ]);
            } else {
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'message' => 'No late fees could be waived. They may have been already paid or waived.'
                ], 422);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Failed to waive late fees", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while waiving late fees: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Manually carry over excess payment from one schedule to another
     */
    public function carryOverExcess(Request $request)
    {
        try {
            // Authorization check - only superadmin can carry over payment allocations
            $user = auth()->user();
            if (!$user?->isSuperAdmin()) {
                return redirect()->back()->with('error', 'Unauthorized. Only the Super Administrator can carry over excess payments.');
            }

            $validated = $request->validate([
                'schedule_id' => 'required|exists:loan_schedules,id',
                'loan_id' => 'required|exists:personal_loans,id',
                'target_action' => 'required|in:next_schedule,late_fees,specific',
                'target_schedule_id' => 'required_if:target_action,specific|exists:loan_schedules,id',
                'carry_note' => 'nullable|string|max:500'
            ]);
            
            DB::beginTransaction();
            
            $sourceSchedule = DB::table('loan_schedules')->where('id', $validated['schedule_id'])->first();
            
            // Calculate excess on source schedule
            $actualPayments = DB::table('repayments')
                ->where('schedule_id', $sourceSchedule->id)
                ->where('status', 1)
                ->sum('amount');
            
            $due = $sourceSchedule->principal + $sourceSchedule->interest;
            $excess = $actualPayments - $due;
            
            if ($excess <= 0) {
                return redirect()->back()->with('error', 'No excess payment found on this schedule.');
            }
            
            $targetSchedule = null;
            
            // Determine target based on action
            if ($validated['target_action'] === 'next_schedule') {
                // Find next unpaid schedule
                $targetSchedule = DB::table('loan_schedules')
                    ->where('loan_id', $validated['loan_id'])
                    ->where('status', 0)
                    ->where('id', '>', $validated['schedule_id'])
                    ->orderBy('payment_date')
                    ->first();
                    
                if (!$targetSchedule) {
                    return redirect()->back()->with('error', 'No unpaid schedule found to carry over to.');
                }
                
            } elseif ($validated['target_action'] === 'late_fees') {
                // Apply to late fees on the same schedule
                // Create a late fee payment record
                $lateFees = DB::table('late_fees')
                    ->where('schedule_id', $validated['schedule_id'])
                    ->where('status', 0)
                    ->get();
                
                if ($lateFees->isEmpty()) {
                    return redirect()->back()->with('error', 'No unpaid late fees found on this schedule.');
                }
                
                $remainingExcess = $excess;
                foreach ($lateFees as $lateFee) {
                    if ($remainingExcess <= 0) break;
                    
                    $paymentAmount = min($remainingExcess, $lateFee->amount);
                    
                    DB::table('late_fees')->where('id', $lateFee->id)->update([
                        'status' => $paymentAmount >= $lateFee->amount ? 1 : 0,
                        'date_paid' => now()
                    ]);
                    
                    $remainingExcess -= $paymentAmount;
                }
                
                DB::commit();
                return redirect()->back()->with('success', 'Excess payment of UGX ' . number_format($excess, 0) . ' applied to late fees.');
                
            } elseif ($validated['target_action'] === 'specific') {
                $targetSchedule = DB::table('loan_schedules')->where('id', $validated['target_schedule_id'])->first();
            }
            
            // Apply excess to target schedule by creating a repayment record
            if ($targetSchedule) {
                // Update target schedule's paid amount
                DB::table('loan_schedules')->where('id', $targetSchedule->id)->update([
                    'paid' => DB::raw('paid + ' . $excess)
                ]);
                
                // Create audit log
                DB::table('repayments')->insert([
                    'loan_id' => $validated['loan_id'],
                    'schedule_id' => $targetSchedule->id,
                    'member_id' => $sourceSchedule->member_id,
                    'amount' => $excess,
                    'payment_method' => 'CARRY_OVER',
                    'reference_number' => 'CARRY-' . $validated['schedule_id'] . '-TO-' . $targetSchedule->id,
                    'status' => 1,
                    'payment_status' => 'Completed',
                    'pay_status' => 'Completed',
                    'notes' => ($validated['carry_note'] ?? 'Manual carry-over') . ' (From Schedule ' . $validated['schedule_id'] . ')',
                    'date_created' => now(),
                    'date_updated' => now(),
                    'added_by' => auth()->id()
                ]);
                
                DB::commit();
                
                // Check if loan should be closed now that carry-over applied
                $this->repaymentService->checkAndCloseLoanIfComplete($validated['loan_id']);
                
                return redirect()->back()->with('success', 'Excess payment of UGX ' . number_format($excess, 0) . ' carried over successfully.');
            }
            
            DB::rollBack();
            return redirect()->back()->with('error', 'Unable to process carry-over.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Carry over excess failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to carry over excess: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all payments for a specific schedule
     */
    public function getSchedulePayments($scheduleId)
    {
        try {
            $payments = Repayment::where('schedule_id', $scheduleId)
                ->where('status', 1)
                ->where('amount', '>', 0)
                ->orderBy('id', 'asc')
                ->get();
            
            $paymentsData = $payments->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'date' => date('d M Y', strtotime($payment->date_created ?? $payment->created_at ?? now())),
                    'amount' => $payment->amount,
                    'amount_formatted' => number_format($payment->amount, 0),
                    'status_badge' => '<span class=\"badge bg-success\">Paid</span>',
                ];
            });
            
            return response()->json([
                'success' => true,
                'payments' => $paymentsData,
                'total' => $payments->sum('amount'),
                'total_formatted' => number_format($payments->sum('amount'), 0)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get schedule payments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load payments'
            ], 500);
        }
    }

    /**
     * Get contact details (phone number and name) from loan
     * 
     * @param PersonalLoan|GroupLoan $loan
     * @param string $loanType
     * @return array ['phone' => string, 'name' => string]
     */
    protected function getLoanContactDetails($loan, $loanType)
    {
        if ($loanType === 'personal') {
            $member = $loan->member;
            return [
                'phone' => $member->contact ?? '',
                'name' => $member->fname . ' ' . $member->lname
            ];
        } else {
            $member = $loan->group;
            return [
                'phone' => $member->contact ?? '',
                'name' => $member->name
            ];
        }
    }

    /**
     * Convert medium code to network name
     * 
     * @param int $medium 1=AIRTEL, 2=MTN
     * @return string|null
     */
    protected function getNetworkFromMedium($medium)
    {
        if (!$medium) {
            return null;
        }
        return $medium == 1 ? 'AIRTEL' : 'MTN';
    }

    /**
     * Validate FlexiPay transaction reference
     * 
     * @param string|null $reference
     * @param int $loanId
     * @throws \Exception if reference is empty
     */
    protected function validateFlexiPayReference($reference, $loanId)
    {
        if (empty($reference)) {
            \Log::error('FlexiPay returned empty reference', [
                'loan_id' => $loanId,
                'reference' => $reference
            ]);
            throw new \Exception('Invalid transaction reference from FlexiPay. Payment not saved. Please try again.');
        }
        
        \Log::info('FlexiPay reference received', [
            'reference' => $reference,
            'length' => strlen($reference),
            'loan_id' => $loanId
        ]);
    }

    /**
     * Find loan by ID from either PersonalLoan or GroupLoan table
     * 
     * @param int $loanId
     * @return array ['loan' => PersonalLoan|GroupLoan|null, 'type' => 'personal'|'group'|null]
     */
    protected function findLoanById($loanId)
    {
        $loan = PersonalLoan::find($loanId);
        if ($loan) {
            return ['loan' => $loan, 'type' => 'personal'];
        }
        
        $loan = GroupLoan::find($loanId);
        if ($loan) {
            return ['loan' => $loan, 'type' => 'group'];
        }
        
        return ['loan' => null, 'type' => null];
    }
}
