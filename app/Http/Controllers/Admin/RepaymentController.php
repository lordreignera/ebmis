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

class RepaymentController extends Controller
{
    protected $mobileMoneyService;

    public function __construct(MobileMoneyService $mobileMoneyService)
    {
        $this->mobileMoneyService = $mobileMoneyService;
    }

    /**
     * Display active loans for repayment management
     */
    public function activeLoans(Request $request)
    {
        // Get active loans from both personal and group loans tables (status = 2 = Disbursed)
        // Optimize by only loading necessary relationships
        $personalLoansQuery = PersonalLoan::where('status', 2)
            ->with([
                'member:id,fname,lname,contact', 
                'branch:id,name', 
                'product:id,name,period_type',
                'schedules',
                'repayments'
            ]);

        $groupLoansQuery = GroupLoan::where('status', 2)
            ->with([
                'group:id,name', 
                'branch:id,name', 
                'product:id,name,period_type',
                'schedules',
                'repayments'
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
        
        // Map and calculate loan details
        $loans = $allLoans->map(function($loan) use ($scheduleTotals) {
            try {
                // Determine loan type and set borrower info
                if (isset($loan->member)) {
                    $loan->loan_type = 'personal';
                    $loan->borrower_name = trim(($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? ''));
                    $loan->phone_number = $loan->member->contact ?? 'N/A';
                } elseif (isset($loan->group)) {
                    $loan->loan_type = 'group';
                    $loan->borrower_name = $loan->group->name ?? 'N/A';
                    $loan->phone_number = 'Group Loan';
                } else {
                    $loan->loan_type = 'unknown';
                    $loan->borrower_name = 'N/A';
                    $loan->phone_number = 'N/A';
                }
            
            $loan->branch_name = $loan->branch->name ?? 'N/A';
            $loan->product_name = $loan->product->name ?? 'N/A';
            $loan->loan_code = $loan->code;
            $loan->principal_amount = (float) $loan->principal;
            
            // Set disbursement date (fallback chain for migrated loans)
            $loan->disbursement_date = $loan->date_approved ?? $loan->datecreated ?? null;
            
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
        $loans = $loans->filter(function($loan) {
            return isset($loan->outstanding_balance) && $loan->outstanding_balance != 0;
        });

        // Paginate manually
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $perPage = 20;
        $currentItems = $loans->slice(($currentPage - 1) * $perPage, $perPage)->all();
        $loans = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $loans->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Get filter options
        $branches = Branch::active()->orderBy('name')->get();
        $products = Product::loanProducts()->active()->orderBy('name')->get();

        // Calculate stats from both loan types
        $allPersonalLoans = PersonalLoan::where('status', 2)->with('repayments', 'schedules')->get();
        $allGroupLoans = GroupLoan::where('status', 2)->with('repayments', 'schedules')->get();
        $allActiveLoans = $allPersonalLoans->concat($allGroupLoans);
        
        $stats = [
            'total_active' => $allActiveLoans->count(),
            'outstanding_amount' => $allActiveLoans->sum(function($loan) {
                $totalPaid = (float) $loan->repayments->where('status', 1)->sum('amount');
                
                // Calculate total payable using DECLINING BALANCE (not doubled interest!)
                $interestRate = (float) $loan->interest / 100;
                $principalPerPeriod = (float) $loan->principal / (float) $loan->period;
                $remainingPrincipal = (float) $loan->principal;
                $totalInterest = 0.0;
                
                for ($i = 0; $i < $loan->period; $i++) {
                    $totalInterest += $remainingPrincipal * $interestRate;
                    $remainingPrincipal -= $principalPerPeriod;
                }
                
                $totalPayable = (float) $loan->principal + $totalInterest;
                return $totalPayable - $totalPaid;
            }),
            'overdue_count' => $allActiveLoans->filter(function($loan) {
                return $loan->schedules->where('status', 0)
                                      ->filter(function($schedule) {
                                          try {
                                              return \Carbon\Carbon::parse($schedule->payment_date)->isPast();
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
        $loan = PersonalLoan::with(['member', 'branch', 'product', 'schedules.repayments', 'repayments'])
                   ->whereIn('status', [2, 3]) // Disbursed or Completed
                   ->find($loanId);
        
        $loanType = 'personal';
        
        if (!$loan) {
            $loan = GroupLoan::with(['group', 'branch', 'product', 'schedules.repayments', 'repayments'])
                       ->whereIn('status', [2, 3]) // Disbursed or Completed
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
        
        // Get disbursement date (might be in different columns depending on table)
        $loan->disbursement_date = $loan->disbursement_date 
            ?? $loan->datecreated 
            ?? $loan->created_at 
            ?? null;
        
        // Calculate payment status from REPAYMENTS table (confirmed payments only)
        $totalPaid = Repayment::where('loan_id', $loan->id)
                             ->where('status', 1)
                             ->sum('amount');
        
        // Calculate total payable from SCHEDULES (principal + interest + any late fees - waived fees)
        // This matches bimsadmin logic exactly
        $totalPayable = $loan->schedules->sum(function($schedule) {
            return $schedule->principal + $schedule->interest;
        });
        
        // Add outstanding late fees (excluding waived ones)
        $totalLateFees = DB::table('late_fees')
            ->where('loan_id', $loan->id)
            ->where('status', 0) // Only pending (unpaid, not waived)
            ->sum('amount');
        
        // Total payable includes pending late fees
        $totalPayableWithFees = $totalPayable + $totalLateFees;
        
        $loan->amount_paid = $totalPaid;
        $loan->outstanding_balance = $totalPayableWithFees - $totalPaid;
        $loan->total_payable = $totalPayableWithFees;
        $loan->payment_percentage = $totalPayableWithFees > 0 ? ($totalPaid / $totalPayableWithFees) * 100 : 0;
        
        // Calculate days overdue
        $overdueSchedules = $loan->schedules()
                                ->where('status', 0)
                                ->where('payment_date', '<', now())
                                ->get();
        
        $loan->days_overdue = $overdueSchedules->count() > 0 ? 
                             now()->diffInDays($overdueSchedules->first()->payment_date) : 0;

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
        
        $schedules = $loan->schedules->map(function($schedule, $index) use ($loan, &$principal, &$globalprincipal, $paidPerSchedule, $pendingPerSchedule) {
            // 1. Calculate "Principal cal Interest" (reducing balance per period)
            $period = floor($loan->period / 2);
            $pricipalcalIntrest = $period > 0 ? ($loan->principal / $period) : 0;
            
            // 2. Use actual interest from schedule (already calculated correctly during disbursement)
            // DO NOT recalculate - it uses the wrong formula with 1.99999999 multiplier
            $intrestamtpayable = $schedule->interest;
            
            // 3. Calculate periods in arrears
            $now = $schedule->date_cleared ? strtotime($schedule->date_cleared) : time();
            $your_date = strtotime($schedule->payment_date);
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
            
            // 4. Calculate late fees (penalty): 6% per period overdue (NOT 10%!)
            $latepayment = (($schedule->principal + $intrestamtpayable) * 0.06) * $dd;
            
            // 4a. Check if late fees have been waived (e.g., November 2025 system upgrade)
            // Sum ALL waived late fees for this schedule (there may be multiple)
            $totalWaivedAmount = DB::table('late_fees')
                ->where('schedule_id', $schedule->id)
                ->where('status', 2) // Waived
                ->sum('amount');
            
            if ($totalWaivedAmount > 0) {
                // Subtract the total waived amount from calculated late fees
                $latepayment = max(0, $latepayment - $totalWaivedAmount);
                
                // DO NOT recalculate periods - periods in arrears should reflect actual time elapsed
                // The waiver reduces the late fee amount, but doesn't change how many periods have passed
                // Keep $dd as calculated above based on actual days overdue
            }
            
            // 5. Get total paid from pre-loaded data (optimized - no N+1 queries)
            $totalPaid = floatval($paidPerSchedule[$schedule->id] ?? 0);
            
            // 6. Get pending count from pre-loaded data (optimized - no N+1 queries)
            $pendingCount = intval($pendingPerSchedule[$schedule->id] ?? 0);
            
            // 7. Allocate payments - BIMSADMIN ORDER: Late Fees → Interest → Principal
            // Late fees paid
            $afterlatepayment = $totalPaid - $latepayment;
            if ($afterlatepayment > 0) {
                $pafterlatepayment = $latepayment; // Full late payment covered
            } else {
                $pafterlatepayment = $totalPaid; // Partial payment
                $afterlatepayment = 0;
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
            $act_bal = ($schedule->principal + $intrestamtpayable + $latepayment) - $totalPaid;
            
            // 9. Attach calculated values to schedule
            $schedule->pricipalcalIntrest = $pricipalcalIntrest;
            $schedule->globalprincipal = $globalprincipal;
            $schedule->intrestamtpayable = $intrestamtpayable;
            $schedule->periods_in_arrears = $dd;
            $schedule->penalty = $latepayment;
            $schedule->total_payment = $schedule->principal + $intrestamtpayable + $latepayment;
            $schedule->principal_paid = $pafterprinicpalpayment;
            $schedule->interest_paid = $pafterinterestpayment;
            $schedule->penalty_paid = $pafterlatepayment;
            $schedule->paid = $totalPaid; // Total amount actually paid (from repayments table)
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

        return view('admin.loans.repayments.schedules', compact(
            'loan', 'schedules', 'nextDue', 'overdueCount', 'overdueAmount'
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
        
        // Note: Validation removed temporarily - the overpayment logic will handle excess amounts
        // by redistributing to next schedules automatically
        
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
                        
                        // Check if next schedule is now fully paid
                        if ($nextSchedule->paid >= $nextSchedule->payment) {
                            $nextSchedule->update(['status' => 1]);
                        }
                    }
                } elseif ($schedule->paid >= $schedule->payment) {
                    // Exact or just enough payment - mark as fully paid
                    $schedule->update(['status' => 1]);
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
    public function partialPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'loan_id' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:mobile_money,cash,bank_transfer',
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

        DB::beginTransaction();

        try {
            $repaymentData = [
                'type' => $this->getPaymentTypeCode($request->payment_method),
                'details' => 'Partial payment: ' . ($request->notes ?: 'No notes'),
                'loan_id' => $request->loan_id,
                'schedule_id' => 0, // Partial payments don't target specific schedules
                'amount' => $request->amount,
                'date_created' => now(),
                'added_by' => auth()->id(),
                'status' => 1, // Assume confirmed for partial payments
                'platform' => 'Web',
                'txn_id' => 'PARTIAL-' . time(),
                'pay_status' => 'SUCCESS',
                'pay_message' => 'Partial payment confirmed',
            ];

            $repayment = Repayment::create($repaymentData);

            // NOTE: personal_loans table doesn't have 'paid' column
            // Payment tracking is done via loan_schedules.paid only

            // Apply to oldest outstanding schedules
            $this->applyPartialPayment($loan, $request->amount);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Partial payment recorded successfully.',
                'repayment_id' => $repayment->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Partial payment failed', [
                'loan_id' => $request->loan_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Partial payment processing failed. Please try again.'
            ], 500);
        }
    }

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
     * Apply partial payment to oldest outstanding schedules
     */
    protected function applyPartialPayment($loan, $amount)
    {
        $schedules = $loan->schedules()
                         ->where('status', 0)
                         ->orderBy('payment_date')
                         ->get();

        $remainingAmount = $amount;

        foreach ($schedules as $schedule) {
            if ($remainingAmount <= 0) break;

            $outstandingAmount = $schedule->payment - ($schedule->paid_amount ?? 0);
            
            if ($outstandingAmount <= 0) continue;

            if ($remainingAmount >= $outstandingAmount) {
                // Can fully pay this schedule
                $schedule->update([
                    'paid_amount' => $schedule->payment,
                    'status' => 1 // Fully paid
                ]);
                $remainingAmount -= $outstandingAmount;
            } else {
                // Partial payment for this schedule
                $newPaidAmount = ($schedule->paid_amount ?? 0) + $remainingAmount;
                $schedule->update(['paid_amount' => $newPaidAmount]);
                $remainingAmount = 0;
            }
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
                // Check if loan has balance field, if not calculate from paid amount
                if (method_exists($loan, 'outstanding_balance')) {
                    if ($loan->outstanding_balance <= 0) {
                        $loan->update(['status' => 3]); // Completed
                    }
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
                // Check loan status
                if (method_exists($loan, 'outstanding_balance')) {
                    if ($loan->outstanding_balance <= 0) {
                        $loan->update(['status' => 3]); // Completed
                    } else {
                        $loan->update(['status' => 2]); // Active
                    }
                }
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
            'addedBy'
        ]);

        // Calculate loan summary
        if ($repayment->loan) {
            // Calculate total paid (all completed repayments for this loan)
            $totalPaid = Repayment::where('loan_id', $repayment->loan_id)
                ->where(function($q) {
                    $q->where('status', 1)
                      ->orWhere('payment_status', 'Completed');
                })
                ->sum('amount');
            
            // Calculate total due
            $totalDue = $repayment->loan->principal + $repayment->loan->interest;
            
            // Calculate outstanding balance
            $outstandingBalance = $totalDue - $totalPaid;
            
            // Add to loan object for view
            $repayment->loan->paid = $totalPaid;
            $repayment->loan->outstanding_balance = max(0, $outstandingBalance); // Don't show negative
            $repayment->loan->total_due = $totalDue;
        }

        return view('admin.repayments.receipt', compact('repayment'));
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
                                    
                                    // Check if next schedule is now fully paid
                                    if ($nextSchedule->paid >= $nextSchedule->payment) {
                                        $nextSchedule->update(['status' => 1]);
                                    }
                                }
                            } elseif ($schedule->paid >= $schedule->payment) {
                                // Exact or just enough payment - mark as fully paid
                                $schedule->update(['status' => 1]);
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
                
                // Calculate late fee (if overdue)
                $lateFee = 0;
                if ($schedule->status != 1) {
                    $dueDate = \Carbon\Carbon::parse($schedule->payment_date);
                    $today = \Carbon\Carbon::now();
                    
                    if ($today->gt($dueDate)) {
                        $daysOverdue = $dueDate->diffInDays($today);
                        
                        // Get period type from product
                        $periodType = optional($loan->product)->period_type ?? '2';
                        $periods = 0;
                        
                        if ($periodType == '3') {
                            $periods = $daysOverdue; // Daily
                        } else if ($periodType == '1') {
                            $periods = ceil($daysOverdue / 7); // Weekly
                        } else {
                            $periods = ceil($daysOverdue / 30); // Monthly
                        }
                        
                        $lateFee = ($scheduleDue * 0.06) * $periods;
                        
                        // Check for waived late fees (sum ALL waived amounts for this schedule)
                        $totalWaivedAmount = DB::table('late_fees')
                            ->where('schedule_id', $schedule->id)
                            ->where('status', 2) // Waived
                            ->sum('amount');
                        
                        if ($totalWaivedAmount > 0) {
                            $lateFee = max(0, $lateFee - $totalWaivedAmount);
                        }
                    }
                }
                
                // Calculate total paid for this schedule
                $totalPaid = Repayment::where('schedule_id', $schedule->id)
                    ->where('status', 1)
                    ->sum('amount');
                
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
                }
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
}