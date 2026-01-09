<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Repayment;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Branch;
use App\Models\LoanSchedule;
use App\Models\Product;
use App\Models\User;
use App\Models\RawPayment;
use App\Services\MobileMoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
 *    - Admin-approved late fee waivers stored in penalty_waivers table
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

    public function __construct(MobileMoneyService $mobileMoneyService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
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
     * Calculate late fee for a schedule with waiver support
     * Centralized method to avoid code duplication
     */
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
        
        // Find any earlier unpaid schedules (by payment_date)
        $earlierUnpaidSchedule = LoanSchedule::where('loan_id', $loanId)
            ->where('id', '!=', $scheduleId)
            ->where('payment_date', '<', $currentSchedule->payment_date)
            ->where(function($query) {
                // Check if total_balance > 0 (not fully paid)
                // Use raw SQL because total_balance is calculated, not stored
                $query->where('status', '!=', 1)
                      ->orWhere(DB::raw('(principal + interest - COALESCE(paid, 0))'), '>', 0.01);
            })
            ->orderBy('payment_date', 'asc')
            ->first();
        
        if ($earlierUnpaidSchedule) {
            return [
                'allowed' => false,
                'message' => sprintf(
                    'Cannot pay this schedule. Please pay the earlier schedule due on %s first. Remaining balance: UGX %s',
                    date('d-M-Y', $this->parsePaymentDate($earlierUnpaidSchedule->payment_date)),
                    number_format(($earlierUnpaidSchedule->principal + $earlierUnpaidSchedule->interest - ($earlierUnpaidSchedule->paid ?? 0)), 0)
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
     * Calculate late fees for a schedule using consistent business logic
     * 
     * FORMULA: Late Fee = (Principal + Interest) × 6% × Periods Overdue
     * 
     * PERIOD CALCULATION:
     * - Weekly (type=1): days_overdue ÷ 7 (rounded up)
     * - Monthly (type=2): days_overdue ÷ 30 (rounded up)
     * - Daily (type=3): days_overdue ÷ 1 (exact days)
     * 
     * FREEZE LOGIC: Periods freeze when total balance (P+I+Late Fees) = 0
     * - Uses payment date/date_cleared for freeze calculation
     * - Continues accumulating if any balance remains
     * 
     * WAIVERS: Deducts any admin-approved late_fee_waivers from penalty_waivers table
     * 
     * @param object $schedule - loan_schedules record with payment_date, principal, interest
     * @param object $loan - personal_loans record with product relationship
     * @return array ['gross' => total_late_fee, 'net' => after_waivers, 'waived' => waiver_amount, 'days_overdue' => days, 'periods_overdue' => periods]
     */
    protected function calculateLateFee($schedule, $loan)
    {
        $now = time();
        $your_date = $this->parsePaymentDate($schedule->payment_date);
        $d = max(0, floor(($now - $your_date) / 86400));
        
        $dd = 0;
        if ($d > 0) {
            $period_type = $loan->product ? $loan->product->period_type : '1';
            $dd = $period_type == '1' ? ceil($d / 7) : ($period_type == '2' ? ceil($d / 30) : $d);
        }
        
        $intrestamtpayable = $schedule->interest;
        $latepaymentOriginal = (($schedule->principal + $intrestamtpayable) * 0.06) * $dd;
        
        // Check for waivers
        $totalWaivedAmount = DB::table('late_fees')
            ->where('schedule_id', $schedule->id)
            ->where('status', 2)
            ->sum('amount');
        
        $latepayment = max(0, $latepaymentOriginal - $totalWaivedAmount);
        
        return [
            'days_overdue' => $d,
            'periods_overdue' => $dd,
            'original' => $latepaymentOriginal,
            'waived' => $totalWaivedAmount,
            'net' => $latepayment
        ];
    }

    /**
     * Display active loans for repayment management
     */
    public function activeLoans(Request $request)
    {
        // Get active loans from both personal and group loans tables
        // Include status 2 (Disbursed) AND status 3 (marked as closed but may have unpaid schedules)
        // We'll filter out truly closed loans later using getActualStatus()
        $personalLoansQuery = PersonalLoan::whereIn('status', [2, 3])
            ->with([
                'member:id,fname,lname,contact', 
                'branch:id,name', 
                'product:id,name,period_type',
                'schedules',
                'repayments',
                'disbursements' => function($query) {
                    $query->where('status', 2)->orderBy('created_at', 'desc');
                }
            ]);

        $groupLoansQuery = GroupLoan::whereIn('status', [2, 3])
            ->with([
                'group:id,name', 
                'branch:id,name', 
                'product:id,name,period_type',
                'schedules',
                'repayments',
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
        $beforeFilterCount = $allLoans->count();
        
        $allLoans = $allLoans->filter(function($loan) {
            $actualStatus = $loan->getActualStatus();
            
            // Debug: Log what we're checking
            $schedules = $loan->schedules ?? collect();
            $unpaidCount = $schedules->where('status', '!=', 1)->count();
            
            \Log::info("Active Loans Filter - Loan {$loan->id} | Code: {$loan->code}", [
                'database_status' => $loan->status,
                'actual_status' => $actualStatus,
                'schedules_count' => $schedules->count(),
                'unpaid_schedules' => $unpaidCount,
                'included' => $actualStatus === 'running' && $unpaidCount > 0 ? 'YES' : 'NO'
            ]);
            
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
        
        $afterFilterCount = $allLoans->count();
        \Log::info("Active Loans Filter Complete", [
            'before_filter' => $beforeFilterCount,
            'after_filter' => $afterFilterCount,
            'filtered_out' => $beforeFilterCount - $afterFilterCount
        ]);
        
        \Log::info("Active Loans IDs after filter", [
            'loan_ids' => $allLoans->pluck('id')->toArray()
        ]);

        // Pre-calculate schedule totals with single query per loan type (huge performance boost!)
        $loanIds = $allLoans->pluck('id')->toArray();
        $scheduleTotals = [];
        
        if (!empty($loanIds)) {
            // Get all schedule totals in one query
            $scheduleTotals = DB::table('loan_schedules')
                ->whereIn('loan_id', $loanIds)
                ->groupBy('loan_id')
                ->select('loan_id', 
                    DB::raw('SUM(principal + interest) as total_payable'),
                    DB::raw('MIN(CASE WHEN status = 0 THEN payment_date END) as next_due_date'),
                    DB::raw('MIN(CASE WHEN status = 0 THEN payment END) as next_due_amount')
                )
                ->get()
                ->keyBy('loan_id');
        }
        
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
        $loans = $allLoans->map(function($loan) use ($scheduleTotals, $memberLoanCounts) {
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
            
            // Calculate outstanding balance using pre-calculated totals (optimized!)
            $totalPaid = (float) $loan->repayments->where('status', 1)->sum('amount');
            
            // Use pre-calculated total from single query
            $scheduleData = $scheduleTotals[$loan->id] ?? null;
            $totalPayable = $scheduleData ? (float) $scheduleData->total_payable : 0.0;
            
            // If no schedules, calculate using declining balance (NOT flat * 2!)
            if ($totalPayable == 0 && $loan->period > 0) {
                $interestRate = (float) $loan->interest / 100;
                $principalPerPeriod = (float) $loan->principal / (float) $loan->period;
                $remainingPrincipal = (float) $loan->principal;
                $totalInterest = 0.0;
                
                for ($i = 0; $i < $loan->period; $i++) {
                    $totalInterest += $remainingPrincipal * $interestRate;
                    $remainingPrincipal -= $principalPerPeriod;
                }
                
                $totalPayable = (float) $loan->principal + $totalInterest;
            }
            
            $loan->outstanding_balance = $totalPayable - $totalPaid;
            
            // Use pre-calculated next due date/amount from single query (optimized!)
            if ($scheduleData && $scheduleData->next_due_date) {
                $loan->next_due_date = $scheduleData->next_due_date;
                $loan->next_due_amount = (float) ($scheduleData->next_due_amount ?? 0);
                
                // Calculate days overdue - handle date parsing errors
                try {
                    $paymentDate = \Carbon\Carbon::createFromFormat('d-m-Y', $scheduleData->next_due_date);
                    if (!$paymentDate) {
                        $paymentDate = \Carbon\Carbon::parse($scheduleData->next_due_date);
                    }
                    $loan->days_overdue = $paymentDate->isPast() ? 
                                         (int) $paymentDate->diffInDays(now(), false) : 0;
                } catch (\Exception $e) {
                    $loan->days_overdue = 0;
                }
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
                $loan->next_due_date = null;
                $loan->next_due_amount = 0;
                $loan->days_overdue = 0;
                return $loan;
            }
        });

        // Filter loans with outstanding balance (include overpayments as they need refunds)
        $beforeBalanceFilter = $loans->count();
        $loans = $loans->filter(function($loan) {
            $hasBalance = isset($loan->outstanding_balance) && $loan->outstanding_balance != 0;
            
            if (!$hasBalance && in_array($loan->id, [119, 116, 118, 120, 121, 123, 125, 126])) {
                \Log::warning("Loan {$loan->id} filtered out by outstanding_balance", [
                    'database_status' => $loan->status,
                    'outstanding_balance' => $loan->outstanding_balance ?? 'not set',
                    'total_paid' => $loan->repayments ? $loan->repayments->where('status', 1)->sum('amount') : 0
                ]);
            }
            
            return $hasBalance;
        });
        
        \Log::info("Outstanding balance filter complete", [
            'before' => $beforeBalanceFilter,
            'after' => $loans->count(),
            'filtered_out' => $beforeBalanceFilter - $loans->count()
        ]);

        // Store the full collection for stats calculation
        $allActiveLoansForStats = $loans;

        // Paginate manually
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $perPage = 20;
        $totalLoans = $loans->count();
        $currentItems = $loans->slice(($currentPage - 1) * $perPage, $perPage)->all();
        
        \Log::info("Active Loans Pagination", [
            'total_loans' => $totalLoans,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'current_page_loan_ids' => collect($currentItems)->pluck('id')->toArray()
        ]);
        
        $loans = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $totalLoans,
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Get filter options
        $branches = Branch::active()->orderBy('name')->get();
        $products = Product::loanProducts()->active()->orderBy('name')->get();

        // Calculate stats from the SAME filtered loans we're displaying
        $stats = [
            'total_active' => $totalLoans, // Use the actual count of filtered loans
            'outstanding_amount' => $allActiveLoansForStats->sum('outstanding_balance'), // Use pre-calculated values
            'overdue_count' => $allActiveLoansForStats->filter(function($loan) {
                return $loan->schedules->where('status', 0)
                                      ->filter(function($schedule) {
                                          try {
                                              // Use parsePaymentDate to handle DD-MM-YYYY format correctly
                                              $dueTimestamp = $this->parsePaymentDate($schedule->payment_date);
                                              return $dueTimestamp !== false && $dueTimestamp < time();
                                          } catch (\Exception $e) {
                                              return false;
                                          }
                                      })
                                      ->count() > 0;
            })->count(),
            'collections_today' => (float) (Repayment::whereDate('date_created', today())
                                          ->where('status', 1)
                                          ->sum('amount') ?? 0),
        ];

        return view('admin.loans.active', compact('loans', 'branches', 'products', 'stats'));
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
            ->where('status', 1)
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
        $pendingPerSchedule = DB::table('repayments')
            ->whereIn('schedule_id', $scheduleIds)
            ->where('status', 0)
            ->where('pay_status', 'Pending')
            ->groupBy('schedule_id')
            ->select('schedule_id', DB::raw('COUNT(*) as pending_count'))
            ->pluck('pending_count', 'schedule_id')
            ->toArray();
        
        // Get schedules with payment status - EXACT bimsadmin calculation logic
        $principal = floatval($loan->principal); // Running principal balance
        $globalprincipal = floatval($loan->principal); // Global principal for interest calculation
        $carryOverPayment = 0; // Track overpayments to distribute to next schedules
        
        $schedules = $loan->schedules->map(function($schedule, $index) use ($loan, &$principal, &$globalprincipal, $paidPerSchedule, $lateFeesPaidPerSchedule, $pendingPerSchedule, &$carryOverPayment) {
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
                        ->where('status', 1)
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
            $currentLateFeeData = $this->calculateLateFee($schedule, $loan);
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
                
                $intrestamtpayable = $schedule->interest;
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
                    'original' => $latepaymentOriginal,
                    'waived' => $totalWaivedAmount,
                    'net' => $latepayment
                ];
            } else {
                // Balance NOT zero - late fees and periods continue accumulating based on TODAY
                // This includes cases where status=1 but late fees are unpaid
                $lateFeeData = $this->calculateLateFee($schedule, $loan);
            }
            
            $latepaymentOriginal = $lateFeeData['original'];
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
            $paidFromRepayments = floatval($paidPerSchedule[$schedule->id] ?? 0);
            $paidFromLateFees = floatval($lateFeesPaidPerSchedule[$schedule->id] ?? 0);
            
            // IMPORTANT: Use ONLY actual payments from repayments table
            // Ignore old schedule.paid field which has incorrect carry-over data
            $actualPaymentReceived = $paidFromRepayments;
            $totalPaid = $actualPaymentReceived;
            
            // 5a. REMOVED: Automatic carry-over disabled - excess stays on original schedule
            // Admins will manually carry over excess using "Carry Over" button
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
            $scheduleDue = $schedule->principal + $intrestamtpayable + $latepayment;
            $act_bal = $scheduleDue - $totalPaid;
            
            // 8a. Handle overpayments - SHOW excess in separate column
            if ($act_bal < 0) {
                // Overpayment: Put excess in "Excess Amount" column
                $schedule->excess_amount = abs($act_bal);
                $act_bal = 0; // Balance should be 0 (fully paid)
            } else {
                $schedule->excess_amount = 0;
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
            $schedule->total_balance = $act_bal;
            $schedule->principal_balance = $principal;
            $schedule->pending_count = $pendingCount;
            
            // Set payment status for filtering next due
            $schedule->payment_status = $schedule->status == 0 ? 'pending' : 'paid';
            $schedule->is_overdue = $d > 0 && $schedule->status == 0;
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
            $loan = PersonalLoan::find($loanId);
            if (!$loan) {
                $loan = GroupLoan::find($loanId);
            }
            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ], 404);
            }
            
            $nextSchedule = LoanSchedule::where('loan_id', $loanId)
                                       ->where('status', 0) // Unpaid
                                       ->orderBy('payment_date')
                                       ->first();
            
            if ($nextSchedule) {
                return response()->json([
                    'success' => true,
                    'schedule' => [
                        'id' => $nextSchedule->id,
                        'due_date' => \Carbon\Carbon::parse($nextSchedule->payment_date)->format('M d, Y'),
                        'expected_amount' => number_format($nextSchedule->payment, 2),
                        'payment_amount' => $nextSchedule->payment,
                        'principal' => $nextSchedule->principal ?? 0,
                        'interest' => $nextSchedule->interest ?? 0,
                        'penalty' => $nextSchedule->penalty ?? 0,
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

        DB::beginTransaction();

        try {
            // Convert payment method to numeric code
            // Legacy system: 1=cash, 2=mobile_money, 3=bank/cheque
            $paymentTypeCode = is_numeric($paymentMethod) 
                ? (int)$paymentMethod 
                : $this->getPaymentTypeCode($paymentMethod);
            
            $repaymentData = [
                'type' => $paymentTypeCode,
                'details' => $request->input('notes') ?: $request->input('details', 'Quick repayment'),
                'loan_id' => $request->loan_id,
                'schedule_id' => $request->input('schedule_id', 0),
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
                    
                    $repaymentData['status'] = 0; // Pending mobile money confirmation
                    $repaymentData['txn_id'] = $flexipayReference;
                    $repaymentData['transaction_reference'] = $flexipayReference; // Also set for consistency
                    $repaymentData['pay_status'] = 'PENDING';
                    $repaymentData['pay_message'] = 'Mobile money collection initiated - check your phone';
                    
                    // Increment pending_count on the schedule (bimsadmin style)
                    if ($request->has('s_id')) {
                        DB::table('loan_schedules')
                            ->where('id', $request->s_id)
                            ->increment('pending_count');
                    }
                    
                    // Create raw_payments record for tracking (bimsadmin style)
                    RawPayment::create([
                        'trans_id' => $mobileMoneyResult['reference'],
                        'phone' => $this->mobileMoneyService->formatPhoneNumber($phone),
                        'amount' => $request->amount,
                        'ref' => '', // FlexiPay reference (empty initially)
                        'message' => 'Payment initiated',
                        'status' => 'Processed', // Match bimsadmin format
                        'pay_status' => '00', // Actual status code - pending
                        'pay_message' => 'Completed successfully',
                        'date_created' => now(),
                        'type' => 'repayment', // Must be 'repayment' not 'collection'
                        'direction' => 'cash_in',
                        'added_by' => auth()->id(),
                        'raw_message' => serialize(['schedule_id' => $request->s_id, 'loan_id' => $request->loan_id, 'member_id' => $loan->member_id]),
                    ]);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Mobile money collection failed: ' . $mobileMoneyResult['message']
                    ], 400);
                }
            } else {
                // For cash/bank transfers/cheque, mark as confirmed - generate FlexiPay-format reference
                $reference = 'EbP' . str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
                
                $repaymentData['status'] = 1;
                $repaymentData['pay_status'] = 'SUCCESS';
                $repaymentData['pay_message'] = 'Payment confirmed';
                $repaymentData['txn_id'] = $reference;
                $repaymentData['transaction_reference'] = $reference; // Set both fields for consistency
            }

            $repayment = Repayment::create($repaymentData);

            // Update loan balance and schedules if confirmed
            if ($repaymentData['status'] == 1) {
                // NOTE: personal_loans table doesn't have 'paid' column
                // Payment tracking is done via loan_schedules.paid only
                $this->updateLoanSchedules($loan, $repayment);
                
                // Check if loan is fully paid by checking ALL schedules
                $this->checkAndCloseLoanIfComplete($loan->id);
            }

            DB::commit();

            $message = "Payment recorded successfully.";
            if ($paymentTypeCode == 1) {
                $message = "Collection request sent to " . $request->phone . ". Please check your phone and enter your Mobile Money PIN to complete the payment.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'repayment_id' => $repayment->id,
                'payment_type' => $paymentTypeCode
            ]);

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

        // Security check: Only Super Administrator and Administrator can use Cash (1) or Bank Transfer (3)
        if (in_array($request->type, [1, 3]) && !auth()->user()->hasRole(['Super Administrator', 'Administrator'])) {
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
                    'message' => 'Access denied. Only Super Administrator and Administrator can record Cash or Bank Transfer payments.'
                ], 403);
            }
            
            return redirect()->back()
                ->with('error', 'Access denied. Only Super Administrator and Administrator can record Cash or Bank Transfer payments.')
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
        
        // Use schedule.paid which includes both direct payments and carry-overs
        $alreadyPaid = floatval($schedule->paid ?? 0);
        
        // Calculate late fees
        $loan = PersonalLoan::with('product')->find($request->loan_id);
        $lateFeeData = $this->calculateLateFee($schedule, $loan);
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
        
        // Get member/group for contact details
        if ($loanType === 'personal') {
            $member = $loan->member;
            $phoneNumber = $member->contact;
            $memberName = $member->fname . ' ' . $member->lname;
        } else {
            $member = $loan->group;
            $phoneNumber = $member->contact ?? '';
            $memberName = $member->name;
        }

        DB::beginTransaction();

        try {
            // Determine network name from medium (for mobile money)
            $network = null;
            if ($request->type == 2 && $request->medium) {
                $network = $request->medium == 1 ? 'AIRTEL' : 'MTN';
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
                    
                    $repaymentData['status'] = 0; // Pending
                    $repaymentData['txn_id'] = $flexipayReference;
                    $repaymentData['transaction_reference'] = $flexipayReference; // Also set this for consistency
                    $repaymentData['pay_status'] = 'PENDING';
                    $repaymentData['pay_message'] = 'Mobile money collection initiated';
                    $repaymentData['network'] = $network;
                    $repaymentData['phone_number'] = $phoneNumber;
                    
                    // Increment pending_count on the schedule (bimsadmin style)
                    $schedule->increment('pending_count');
                    
                    // Create raw_payments record for tracking (bimsadmin format)
                    \App\Models\RawPayment::create([
                        'trans_id' => $mobileMoneyResult['reference'], // THIS is what CheckTransactions searches for
                        'phone' => $phoneNumber,
                        'amount' => $request->amount,
                        'ref' => '', // FlexiPay reference (empty initially)
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
                            'transaction_id' => $mobileMoneyResult['reference']
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
                // Cash (type=1) or Bank Transfer (type=3) - immediately confirmed by Super Admin/Administrator
                // Generate proper reference matching FlexiPay format: EbP##########
                $reference = 'EbP' . str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
                
                $paymentMethodName = $request->type == 1 ? 'Cash' : 'Bank Transfer';
                
                $repaymentData['status'] = 1; // Confirmed immediately
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
                
                $repayment = Repayment::create($repaymentData);
                
                // Update schedule for confirmed payments
                // NOTE: personal_loans table doesn't have 'paid' column - tracking is done via loan_schedules
                
                // Handle payment allocation with overpayment redistribution
                $paymentAmount = $request->amount;
                $schedule->increment('paid', $paymentAmount);
                
                // Check if there's an overpayment on this schedule
                if ($schedule->paid > $schedule->payment) {
                    $overpayment = $schedule->paid - $schedule->payment;
                    
                    // Cap the current schedule paid amount to its required payment
                    $schedule->update([
                        'paid' => $schedule->payment,
                        'status' => 1
                    ]);
                    
                    // Apply overpayment to next unpaid schedule
                    $nextSchedule = \App\Models\LoanSchedule::where('loan_id', $loan->id)
                        ->where('status', '!=', 1)
                        ->where('id', '>', $schedule->id)
                        ->orderBy('id')
                        ->first();
                    
                    if ($nextSchedule && $overpayment > 0) {
                        $nextSchedule->increment('paid', $overpayment);
                        
                        // Check if next schedule is now fully paid (including late fees)
                        $nextLateFees = DB::table('late_fees')
                            ->where('schedule_id', $nextSchedule->id)
                            ->where('status', 0)
                            ->sum('amount');
                        $nextTotalDue = $nextSchedule->payment + $nextLateFees;
                        
                        if ($nextSchedule->paid >= $nextTotalDue) {
                            $nextSchedule->update(['status' => 1]);
                        }
                    }
                } elseif ($schedule->paid >= $schedule->payment) {
                    // Check if late fees are also paid before marking as fully paid
                    $pendingLateFees = DB::table('late_fees')
                        ->where('schedule_id', $schedule->id)
                        ->where('status', 0)
                        ->sum('amount');
                    $totalDue = $schedule->payment + $pendingLateFees;
                    
                    if ($schedule->paid >= $totalDue) {
                        $schedule->update(['status' => 1]);
                    }
                }
                
                // Check if loan is fully paid by checking ALL schedules
                $this->checkAndCloseLoanIfComplete($loan->id);
                
                DB::commit();
                
                // Return JSON for AJAX
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $paymentMethodName . ' payment of UGX ' . number_format($request->amount) . ' has been confirmed and recorded successfully. Schedule updated to PAID.'
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
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:mobile_money,cash,bank_transfer',
            'schedules' => 'required|string',
            'txn_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find loan from either table
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

        // Parse schedules JSON
        $schedules = json_decode($request->schedules, true);
        if (!is_array($schedules) || count($schedules) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid schedules data'
            ], 422);
        }

        DB::beginTransaction();

        try {
            $totalAmount = floatval($request->amount);
            $remainingAmount = $totalAmount;
            $paymentsCreated = 0;
            $schedulesPaid = [];

            // Process each selected schedule
            foreach ($schedules as $scheduleData) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $scheduleId = $scheduleData['schedule_id'];
                $scheduleBalance = floatval($scheduleData['balance']);

                // Get the actual schedule from database
                $schedule = DB::table('loan_schedules')->where('id', $scheduleId)->first();
                if (!$schedule) {
                    continue;
                }

                // Determine payment amount for this schedule
                $paymentAmount = min($remainingAmount, $scheduleBalance);

                // Create repayment record
                $repaymentData = [
                    'type' => $this->getPaymentTypeCode($request->payment_method),
                    'details' => 'Balance payment: ' . ($request->notes ?: 'Schedule balance cleared'),
                    'loan_id' => $request->loan_id,
                    'schedule_id' => $scheduleId,
                    'amount' => $paymentAmount,
                    'date_created' => now(),
                    'added_by' => auth()->id(),
                    'status' => 1, // Confirmed
                    'platform' => 'Web',
                    'txn_id' => $request->txn_reference ?: ('BAL-' . time() . '-' . $scheduleId),
                    'pay_status' => 'SUCCESS',
                    'pay_message' => 'Balance payment confirmed',
                ];

                $repayment = Repayment::create($repaymentData);
                $paymentsCreated++;

                // Calculate new total paid for this schedule
                $totalPaid = DB::table('repayments')
                    ->where('schedule_id', $scheduleId)
                    ->where('status', 1)
                    ->sum('amount');

                $totalDue = $schedule->principal + $schedule->interest;

                // Mark schedule as paid if fully paid (with 0.99 tolerance for rounding)
                if ($totalPaid >= ($totalDue - 0.99)) {
                    DB::table('loan_schedules')
                        ->where('id', $scheduleId)
                        ->update(['status' => 1]);
                    
                    $schedulesPaid[] = $scheduleData['due_date'];
                }

                $remainingAmount -= $paymentAmount;
            }
            
            // Check if loan is fully paid after balance payment
            $this->checkAndCloseLoanIfComplete($loan->id);

            DB::commit();

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
                'reference' => $transactionRef
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
        
        $query = Repayment::with(['loan.member', 'loan.product', 'addedBy']);

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
                20,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            $totals = [
                'total_amount' => 0,
                'total_principal' => 0,
                'total_interest' => 0,
                'total_fees' => 0,
                'total_penalty' => 0,
            ];
            
            $branches = Branch::active()->get() ?? collect();
            
            return view('admin.repayments.index', compact('repayments', 'branches', 'totals', 'loanType'))
                ->with('info', 'Repayment tracking for ' . ucfirst($loanType) . ' loans will be available once loans are disbursed and repayments begin. The system is ready to track repayments for school, student, and staff loans.');
        }

        // Default behavior: show personal and group loan repayments only
        // Filter to show only successful repayments
        $query->where(function($q) {
            $q->where('status', 1) // Traditional successful payments
              ->orWhere('payment_status', 'Completed'); // Mobile money completed payments
        });
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('loan', function($loanQuery) use ($search) {
                    $loanQuery->where('code', 'like', "%{$search}%")
                             ->orWhereHas('member', function($memberQuery) use ($search) {
                                 $memberQuery->where('fname', 'like', "%{$search}%")
                                           ->orWhere('lname', 'like', "%{$search}%")
                                           ->orWhere('code', 'like', "%{$search}%");
                             });
                });
            });
        }

        // Filter by date range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('date_created', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('date_created', '<=', $request->end_date);
        }

        // Filter by payment method
        if ($request->has('method') && $request->method !== '') {
            $query->where('type', $request->method);
        }

        $repayments = $query->orderBy('date_created', 'desc')->paginate(20);

        $branches = Branch::active()->get() ?? collect();

        // Calculate totals for current filter
        $totalQuery = clone $query;
        $totals = [
            'total_amount' => $totalQuery->sum('amount'),
            'total_principal' => 0, // We don't separate these in old structure
            'total_interest' => 0,
            'total_fees' => 0,
            'total_penalty' => 0,
        ];

        return view('admin.repayments.index', compact('repayments', 'branches', 'totals', 'loanType'));
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
                'status' => $validated['status'] ? 1 : 0,
                'platform' => 'Web',
                'txn_id' => $validated['txn_id'],
                'pay_status' => $validated['status'] ? 'SUCCESS' : 'PENDING',
                'pay_message' => $validated['status'] ? 'Payment confirmed' : 'Payment pending verification',
            ];

            $repayment = Repayment::create($repaymentData);

            // NOTE: personal_loans table doesn't have 'paid' column
            // Payment tracking is done via loan_schedules.paid only
            if ($validated['status']) {
                // Check if loan is fully paid - validates ALL schedules before closing
                $this->checkAndCloseLoanIfComplete($validated['loan_id']);
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

        try {
            DB::beginTransaction();

            $loan = $repayment->loan;
            $oldAmount = $repayment->amount;
            $wasConfirmed = $repayment->status == 1;

            // Reverse the old repayment effects if it was confirmed
            if ($wasConfirmed) {
                $loan->decrement('paid', $oldAmount);
            }

            // Update repayment data
            $updateData = [
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'details' => $validated['details'],
                'txn_id' => $validated['txn_id'],
                'status' => $validated['status'] ? 1 : 0,
                'pay_status' => $validated['status'] ? 'SUCCESS' : 'PENDING',
                'pay_message' => $validated['status'] ? 'Payment confirmed' : 'Payment pending verification',
            ];

            if (isset($validated['date'])) {
                $updateData['date_created'] = $validated['date'];
            }

            $repayment->update($updateData);

            // NOTE: personal_loans table doesn't have 'paid' column
            // Payment tracking is done via loan_schedules.paid only
            if ($validated['status']) {
                // Check if loan is fully paid - validates ALL schedules before closing
                $this->checkAndCloseLoanIfComplete($repayment->loan_id);
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

            $loan = $repayment->loan;

            // Reverse the repayment effects if it was confirmed
            if ($repayment->status == 1) {
                $loan->decrement('paid', $repayment->amount);
                
                // Update loan status back to active
                $loan->update(['status' => 2]); // Back to active
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
                $schedule->update([
                    'status' => 1, // Paid
                    'paid_amount' => $totalDue,
                    'payment_date' => $repayment->date
                ]);
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
            $lateFeeData = $this->calculateLateFee($schedule, $repayment->loan);
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
                $lateFeeData = $this->calculateLateFee($schedule, $loan);
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
                            'pay_status' => 'SUCCESS',
                            'pay_message' => 'Payment confirmed via mobile money'
                        ]);
                        
                        // Get loan and schedule
                        $loan = Loan::find($repayment->loan_id);
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
                            
                            // Check if there's an overpayment on this schedule
                            if ($schedule->paid > $schedule->payment) {
                                $overpayment = $schedule->paid - $schedule->payment;
                                
                                // Cap the current schedule paid amount to its required payment
                                $schedule->update([
                                    'paid' => $schedule->payment,
                                    'status' => 1
                                ]);
                                
                                // Apply overpayment to next unpaid schedule
                                $nextSchedule = \App\Models\LoanSchedule::where('loan_id', $loan->id)
                                    ->where('status', '!=', 1)
                                    ->where('id', '>', $schedule->id)
                                    ->orderBy('id')
                                    ->first();
                                
                                if ($nextSchedule && $overpayment > 0) {
                                    $nextSchedule->increment('paid', $overpayment);
                                    
                                    // Check if next schedule is now fully paid (including late fees)
                                    $nextLateFees = DB::table('late_fees')
                                        ->where('schedule_id', $nextSchedule->id)
                                        ->where('status', 0)
                                        ->sum('amount');
                                    $nextTotalDue = $nextSchedule->payment + $nextLateFees;
                                    
                                    if ($nextSchedule->paid >= $nextTotalDue) {
                                        $nextSchedule->update(['status' => 1]);
                                    }
                                }
                            } elseif ($schedule->paid >= $schedule->payment) {
                                // Check if late fees are also paid before marking as fully paid
                                $pendingLateFees = DB::table('late_fees')
                                    ->where('schedule_id', $schedule->id)
                                    ->where('status', 0)
                                    ->sum('amount');
                                $totalDue = $schedule->payment + $pendingLateFees;
                                
                                if ($schedule->paid >= $totalDue) {
                                    $schedule->update(['status' => 1]);
                                }
                            }
                            
                            // Check if loan is fully paid by checking ALL schedules
                            $this->checkAndCloseLoanIfComplete($loan->id);
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
            
            // Find the repayment by transaction reference
            $repayment = Repayment::where('transaction_reference', $transactionRef)->first();
            
            if (!$repayment) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Repayment not found'
                ], 404);
            }

            // If already completed, return success
            if ($repayment->payment_status === 'Completed') {
                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed successfully'
                ]);
            }

            // Check status with FlexiPay
            $mobileMoneyService = app(\App\Services\MobileMoneyService::class);
            $statusResult = $mobileMoneyService->checkTransactionStatus($transactionRef);

            \Log::info("FlexiPay Repayment Status Result", [
                'transaction_ref' => $transactionRef,
                'status' => $statusResult['status'] ?? 'unknown',
                'full_result' => $statusResult
            ]);

            // Update repayment based on status
            if ($statusResult['status'] === 'completed') {
                DB::beginTransaction();
                
                try {
                    $schedule = LoanSchedule::find($repayment->schedule_id);
                    $loan = Loan::find($repayment->loan_id);
                    
                    // Update repayment status
                    $repayment->update([
                        'payment_status' => 'Completed',
                        'status' => 1, // Mark as confirmed/completed
                        'payment_raw' => json_encode($statusResult)
                    ]);

                    // Decrement pending_count on the schedule
                    if ($schedule->pending_count > 0) {
                        $schedule->decrement('pending_count');
                    }

                    // Update schedule paid amount
                    $schedule->increment('paid', $repayment->amount);
                    
                    // Check if schedule is fully paid (with 1 UGX rounding tolerance)
                    $difference = abs($schedule->payment - $schedule->paid);
                    if ($schedule->paid >= $schedule->payment || $difference <= 1.0) {
                        $schedule->update([
                            'status' => 1, // Fully paid
                            'paid' => $schedule->payment // Ensure exact match to prevent rounding issues
                        ]);
                    }

                    // Calculate and apply late fees if payment is overdue
                    $dueDate = \Carbon\Carbon::parse($schedule->payment_date);
                    $paymentDate = now();
                    
                    if ($paymentDate->isAfter($dueDate)) {
                        $daysLate = $dueDate->diffInDays($paymentDate);
                        
                        // Get late fee rate from product or use default
                        $lateFeePerDay = $loan->product->late_fee_per_day ?? 1000; // UGX per day
                        $lateFeeAmount = $daysLate * $lateFeePerDay;
                        
                        // Find or get late fee type
                        $lateFeeType = \App\Models\FeeType::where('name', 'LIKE', '%late%')
                                                         ->orWhere('name', 'LIKE', '%penalty%')
                                                         ->first();
                        
                        if ($lateFeeType && $lateFeeAmount > 0) {
                            // Get member_id from loan
                            $member_id = $loan->member_id;
                            
                            // Create late fee record
                            \App\Models\Fee::create([
                                'member_id' => $member_id,
                                'loan_id' => $loan->id,
                                'fees_type_id' => $lateFeeType->id,
                                'amount' => $lateFeeAmount,
                                'description' => "Late payment fee: {$daysLate} days overdue for Schedule #{$schedule->id}",
                                'status' => 0, // Unpaid
                                'payment_type' => 2, // Mobile Money
                                'added_by' => auth()->id(),
                                'datecreated' => now()
                            ]);
                            
                            // Update schedule with late fees
                            $schedule->update([
                                'penalty_amount' => ($schedule->penalty_amount ?? 0) + $lateFeeAmount
                            ]);
                            
                            \Log::info("Late fee applied", [
                                'repayment_id' => $repayment->id,
                                'days_late' => $daysLate,
                                'late_fee_amount' => $lateFeeAmount
                            ]);
                        }
                    }
                    
                    // Check if loan is fully paid
                    $totalPaid = Repayment::where('loan_id', $loan->id)
                                         ->where('payment_status', 'Completed')
                                         ->sum('amount');
                    
                    $totalDue = $loan->principal + $loan->interest;
                    
                    if ($totalPaid >= $totalDue) {
                        $loan->update(['status' => 3]); // Completed
                    }
                    
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    \Log::error("Repayment status update failed", [
                        'repayment_id' => $repayment->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment completed successfully'
                ]);
                
            } elseif ($statusResult['status'] === 'failed') {
                // Check if payment is recent (within 2 minutes) - FlexiPay retries 3 times
                $createdAt = \Carbon\Carbon::parse($repayment->date_created);
                $ageInMinutes = $createdAt->diffInMinutes(now());
                
                if ($ageInMinutes < 2) {
                    // Payment is recent - don't mark as failed yet
                    \Log::info("Repayment marked as pending - still within retry window", [
                        'repayment_id' => $repayment->id,
                        'age_minutes' => $ageInMinutes,
                        'transaction_ref' => $transactionRef
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'status' => 'pending',
                        'message' => 'Payment being processed - FlexiPay will retry if user cancelled'
                    ]);
                }
                
                // Payment is old enough - mark as failed
                $repayment->update([
                    'payment_status' => 'Failed',
                    'payment_raw' => json_encode($statusResult)
                ]);

                // Decrement pending_count on the schedule since payment failed
                if ($schedule->pending_count > 0) {
                    $schedule->decrement('pending_count');
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
            $pendingRepayments = Repayment::where('schedule_id', $scheduleId)
                ->where('payment_status', 'Pending')
                ->where('type', 2) // Mobile Money type
                ->orderBy('date_created', 'desc')
                ->get(['id', 'amount', 'payment_phone', 'transaction_reference', 'date_created']);

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
     * Only Super Administrator and Administrator can stop loans
     */
    public function stopLoan(Request $request, $loanId)
    {
        try {
            // Check authorization - only superadmin and administrator
            $user = auth()->user();
            if (!$user->hasRole(['Super Administrator', 'superadmin', 'Administrator', 'administrator'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only Super Administrator and Administrator can stop loans.'
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
     * Check if all loan schedules are paid and close the loan if complete
     * NO WAIVERS - all schedules must be fully paid
     */
    private function checkAndCloseLoanIfComplete(int $loanId): bool
    {
        try {
            // Try PersonalLoan first, then GroupLoan
            $loan = PersonalLoan::find($loanId);
            $loanType = 'personal';
            
            if (!$loan) {
                $loan = GroupLoan::find($loanId);
                $loanType = 'group';
            }
            
            if (!$loan) {
                Log::warning("Loan {$loanId} not found");
                return false;
            }
            
            // Get all schedules with repayments
            $schedules = LoanSchedule::where('loan_id', $loanId)->get();
            
            if ($schedules->isEmpty()) {
                return false;
            }
            
            // Calculate total amount due (principal + interest + late fees - waivers)
            $totalDue = 0;
            $allSchedulesPaid = true;
            
            foreach ($schedules as $schedule) {
                $scheduleDue = $schedule->principal + $schedule->interest;
                
                // Calculate late fee using helper method
                $lateFeeData = $this->calculateLateFee($schedule, $loan);
                $lateFee = $lateFeeData['net'];
                
                // Calculate total paid for this schedule
                // FIXED: Also use schedule.paid column as fallback for old loans without repayment records
                $totalPaidFromRepayments = Repayment::where('schedule_id', $schedule->id)
                    ->where('status', 1)
                    ->sum('amount');
                
                // Use the greater of: repayments table sum OR schedule.paid column
                // This handles both new loans (with repayment records) and old loans (direct schedule updates)
                $totalPaid = max($totalPaidFromRepayments, $schedule->paid ?? 0);
                
                $scheduleBalance = ($scheduleDue + $lateFee) - $totalPaid;
                $totalDue += max(0, $scheduleBalance);
                
                if ($scheduleBalance > 0.01) {
                    $allSchedulesPaid = false;
                } else if ($scheduleBalance <= 0.01 && $schedule->status != 1) {
                    // Mark schedule as paid
                    $schedule->update([
                        'status' => 1,
                        'date_cleared' => now()
                    ]);
                }
            }
            
            // If total due is <= 0 (fully paid), close the loan
            if ($totalDue <= 0.01 && $allSchedulesPaid) {
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
     * Waive selected late fees for a loan
     * Only accessible by superadmin and administrator roles
     */
    public function waiveLateFees(Request $request)
    {
        try {
            // Authorization check - only superadmin and administrator can waive late fees
            $user = auth()->user();
            $allowedRoles = ['Super Administrator', 'superadmin', 'Administrator', 'administrator'];
            $hasPermission = false;
            
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    $hasPermission = true;
                    break;
                }
            }
            
            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only administrators can waive late fees.'
                ], 403);
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'loan_id' => 'required|integer|exists:personal_loans,id',
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
            $lateFees = json_decode($request->late_fees, true);
            $waiverReason = $request->waiver_reason;
            
            if (empty($lateFees)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No late fees selected'
                ], 422);
            }
            
            // Get loan and member details
            $loan = PersonalLoan::find($loanId);
            if (!$loan) {
                $loan = GroupLoan::find($loanId);
            }
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
                    $schedule = LoanSchedule::find($scheduleId);
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
            // Authorization check - only superadmin and administrator can carry over
            $user = auth()->user();
            if (!$user->hasRole(['Super Administrator', 'superadmin', 'Administrator', 'administrator'])) {
                return redirect()->back()->with('error', 'Unauthorized. Only administrators can carry over excess payments.');
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
                $this->checkAndCloseLoanIfComplete($validated['loan_id']);
                
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
}
