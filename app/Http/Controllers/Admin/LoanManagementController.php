<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LoanApprovalService;
use App\Services\DisbursementService;
use App\Services\RepaymentService;
use App\Services\LoanScheduleService;
use App\Services\FeeManagementService;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Models\Disbursement;
use App\Models\Repayment;
use App\Models\LoanSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class LoanManagementController extends Controller
{
    private LoanApprovalService $approvalService;
    private DisbursementService $disbursementService;
    private RepaymentService $repaymentService;
    private LoanScheduleService $scheduleService;
    private FeeManagementService $feeService;
    
    public function __construct(
        LoanApprovalService $approvalService,
        DisbursementService $disbursementService,
        RepaymentService $repaymentService,
        LoanScheduleService $scheduleService,
        FeeManagementService $feeService
    ) {
        $this->approvalService = $approvalService;
        $this->disbursementService = $disbursementService;
        $this->repaymentService = $repaymentService;
        $this->scheduleService = $scheduleService;
        $this->feeService = $feeService;
    }
    
    /**
     * Show loan approval page
     */
    public function showLoanApproval($id, $type = 'personal')
    {
        try {
            $loan = $this->getLoanByTypeAndId($type, $id);
            if (!$loan) {
                return redirect()->back()->with('error', 'Loan not found');
            }
            
            $approvalSummary = $this->approvalService->generateApprovalSummary($loan);
            
            return view('admin.loans.approve', [
                'loan' => $loan,
                'approval_summary' => $approvalSummary,
                'loan_type' => $type
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading loan approval page: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading loan details');
        }
    }
    
    /**
     * Process loan approval
     */
    public function approveLoan(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'loan_type' => 'required|string|in:personal,group',
            'charge_type' => 'required|string|in:1,2',
            'comments' => 'nullable|string|max:500'
        ]);
        
        try {
            $loan = $this->getLoanByTypeAndId($request->loan_type, $request->loan_id);
            if (!$loan) {
                return response()->json([
                    'status' => false,
                    'msg' => 'Loan not found'
                ]);
            }
            
            $approvalData = [
                'charge_type' => $request->charge_type,
                'comments' => $request->comments
            ];
            
            if ($request->loan_type === 'personal') {
                $result = $this->approvalService->approvePersonalLoan($loan, $approvalData);
            } else {
                $result = $this->approvalService->approveGroupLoan($loan, $approvalData);
            }
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Loan approval error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'msg' => 'Loan approval failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Reject loan
     */
    public function rejectLoan(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'loan_type' => 'required|string|in:personal,group',
            'comments' => 'required|string|max:500'
        ]);
        
        try {
            $loan = $this->getLoanByTypeAndId($request->loan_type, $request->loan_id);
            if (!$loan) {
                return response()->json([
                    'status' => false,
                    'msg' => 'Loan not found'
                ]);
            }
            
            $result = $this->approvalService->rejectLoan($loan, $request->comments);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Loan rejection error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'msg' => 'Loan rejection failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show disbursement page
     */
    public function showDisbursements()
    {
        try {
            $pendingDisbursements = $this->disbursementService->getPendingDisbursements();
            $statistics = $this->disbursementService->getDisbursementStatistics();
            
            return view('admin.loans.disbursements', [
                'pending_disbursements' => $pendingDisbursements,
                'statistics' => $statistics
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading disbursements page: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading disbursements');
        }
    }
    
    /**
     * Process loan disbursement
     */
    public function processDisbursement(Request $request)
    {
        $request->validate([
            'disbursement_id' => 'required|integer',
            'type' => 'required|string|in:0,1,2', // 0=cash, 1=mobile money, 2=bank
            'account_number' => 'nullable|string',
            'd_date' => 'required|date',
            'inv_id' => 'nullable|integer'
        ]);
        
        // Additional validation for mobile money
        if ($request->type == '1') {
            $request->validate([
                'account_number' => 'required|string|min:10',
                'loan_amt' => 'required|numeric|min:1000'
            ]);
        }
        
        try {
            $disbursementData = [
                'type' => $request->type,
                'account_number' => $request->account_number,
                'd_date' => $request->d_date,
                'inv_id' => $request->inv_id,
                'loan_amt' => $request->loan_amt ?? 0,
                'medium' => $request->medium ?? '2' // Default to MTN
            ];
            
            $result = $this->disbursementService->processLoanDisbursement(
                $request->disbursement_id,
                $disbursementData
            );
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Disbursement processing error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'msg' => 'Disbursement processing failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show repayments page
     */
    public function showRepayments()
    {
        try {
            return view('admin.loans.repayments');
            
        } catch (\Exception $e) {
            Log::error('Error loading repayments page: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading repayments');
        }
    }
    
    /**
     * Process loan repayment
     */
    public function processRepayment(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|integer',
            'amount' => 'required|numeric|min:100',
            'type' => 'required|string|in:1,2,3' // 1=cash, 2=mobile money, 3=bank
        ]);
        
        // Additional validation for mobile money
        if ($request->type == '2') {
            $request->validate([
                'phone' => 'required|string|min:10',
                'network' => 'nullable|string|in:MTN,AIRTEL'
            ]);
        }
        
        try {
            $paymentData = [
                'schedule_id' => $request->schedule_id,
                'amount' => $request->amount,
                'type' => $request->type,
                'phone' => $request->phone ?? '',
                'network' => $request->network ?? null
            ];
            
            $result = $this->repaymentService->processRepayment($paymentData);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Repayment processing error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Repayment processing failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show loan schedule
     */
    public function showLoanSchedule($id, $type = 'personal')
    {
        try {
            $loan = $this->getLoanByTypeAndId($type, $id);
            if (!$loan) {
                return redirect()->back()->with('error', 'Loan not found');
            }
            
            $amortizationTable = $this->scheduleService->calculateAmortizationTable($loan);
            
            return view('admin.loans.schedule', [
                'loan' => $loan,
                'amortization_table' => $amortizationTable,
                'loan_type' => $type
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading loan schedule: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading loan schedule');
        }
    }
    
    /**
     * Generate loan schedule
     */
    public function generateSchedule(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'loan_type' => 'required|string|in:personal,group'
        ]);
        
        try {
            $loan = $this->getLoanByTypeAndId($request->loan_type, $request->loan_id);
            if (!$loan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Loan not found'
                ]);
            }
            
            $result = $this->scheduleService->generateAndSaveSchedule($loan);
            
            return response()->json([
                'success' => $result,
                'message' => $result ? 'Schedule generated successfully' : 'Failed to generate schedule'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Schedule generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Schedule generation failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Show loan fees
     */
    public function showLoanFees($id, $type = 'personal')
    {
        try {
            $loan = $this->getLoanByTypeAndId($type, $id);
            if (!$loan) {
                return redirect()->back()->with('error', 'Loan not found');
            }
            
            $calculatedFees = $this->feeService->calculateLoanFees($loan);
            $existingFees = $this->feeService->getLoanFees($loan);
            $disbursementCalculation = $this->feeService->calculateDisbursementAmount($loan);
            
            return view('admin.loans.fees', [
                'loan' => $loan,
                'calculated_fees' => $calculatedFees,
                'existing_fees' => $existingFees,
                'disbursement_calculation' => $disbursementCalculation,
                'loan_type' => $type
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading loan fees: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error loading loan fees');
        }
    }
    
    /**
     * Process mobile money callback
     */
    public function mobileMoneyCallback(Request $request)
    {
        try {
            Log::info('Mobile money callback received', $request->all());
            
            $result = $this->repaymentService->processPaymentCallback($request->all());
            
            if ($result['success']) {
                return response()->json(['status' => 'success', 'message' => $result['message']]);
            } else {
                return response()->json(['status' => 'error', 'message' => $result['message']], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Mobile money callback error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Callback processing failed'
            ], 500);
        }
    }
    
    /**
     * Test mobile money connection
     */
    public function testMobileMoneyConnection()
    {
        try {
            $result = $this->disbursementService->testMobileMoneyConnection();
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Mobile money connection test error: ' . $e->getMessage());
            
            return response()->json([
                'connection' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Personal loan preview dashboard (real data, monthly)
     */
    public function personalPreviewDashboard(Request $request)
    {
        $selectedMonth = $request->get('month', now()->format('Y-m'));
        $selectedOfficer = $request->get('officer');
        $selectedOfficer = ($selectedOfficer !== null && $selectedOfficer !== '') ? (int) $selectedOfficer : null;

        try {
            $monthStart = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Exception $e) {
            $monthStart = now()->startOfMonth();
            $selectedMonth = $monthStart->format('Y-m');
        }

        $monthEnd = (clone $monthStart)->endOfMonth();
        $monthLabel = $monthStart->format('F Y');

        $disbursementDateCandidates = [];
        if (Schema::hasColumn('disbursements', 'disbursement_date')) {
            $disbursementDateCandidates[] = 'disbursement_date';
        }
        if (Schema::hasColumn('disbursements', 'date_approved')) {
            $disbursementDateCandidates[] = 'date_approved';
        }
        if (Schema::hasColumn('disbursements', 'created_at')) {
            $disbursementDateCandidates[] = 'created_at';
        }
        if (empty($disbursementDateCandidates)) {
            $disbursementDateCandidates[] = 'created_at';
        }
        $disbursementDateExpression = count($disbursementDateCandidates) > 1
            ? 'COALESCE(' . implode(', ', $disbursementDateCandidates) . ')'
            : $disbursementDateCandidates[0];

        $successfulRepaymentsBase = Repayment::query()
            ->where('amount', '>', 0)
            ->whereBetween('date_created', [$monthStart, $monthEnd])
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhere('payment_status', 'Completed');
            })
            ->whereHas('schedule.personalLoan');

        $collectionsTodayAmount = (clone $successfulRepaymentsBase)->sum('amount');
        $collectionsTodayCount = (clone $successfulRepaymentsBase)->count();

        $disbursementsBase = Disbursement::query()
            ->where('loan_type', 1)
            ->where('status', 2)
            ->whereRaw(
                "DATE({$disbursementDateExpression}) BETWEEN ? AND ?",
                [$monthStart->toDateString(), $monthEnd->toDateString()]
            );

        $disbursementsTodayAmount = (clone $disbursementsBase)->sum('amount');
        $disbursementsTodayCount = (clone $disbursementsBase)->count();

        // Pending pipeline should include:
        // 1) Not yet approved loans (verified = 0)
        // 2) Already approved loans that are still awaiting disbursement
        $pendingApprovals = PersonalLoan::query()
            ->where(function ($query) use ($monthStart, $monthEnd) {
                $query->where(function ($q) use ($monthStart, $monthEnd) {
                    $q->where('verified', 0)
                        ->whereBetween('datecreated', [$monthStart, $monthEnd]);
                })->orWhere(function ($q) use ($monthStart, $monthEnd) {
                    $q->where('verified', 1)
                        ->whereRaw('DATE(COALESCE(date_approved, datecreated)) BETWEEN ? AND ?', [
                            $monthStart->toDateString(),
                            $monthEnd->toDateString()
                        ])
                        ->whereDoesntHave('disbursements', function ($d) {
                            $d->where('status', 2);
                        });
                });
            })
            ->count();

        $exceptionsCount = PersonalLoan::query()
            ->where(function ($query) {
                $query->where('verified', 2)
                    ->orWhere('status', 4);
            })
            ->whereBetween('datecreated', [$monthStart, $monthEnd])
            ->count();

        $collectionsQueue = Repayment::with(['schedule.personalLoan.member'])
            ->where('amount', '>', 0)
            ->whereBetween('date_created', [$monthStart, $monthEnd])
            ->where(function ($query) {
                $query->where('status', 1)
                    ->orWhere('payment_status', 'Completed');
            })
            ->whereHas('schedule.personalLoan')
            ->orderByDesc('date_created')
            ->limit(6)
            ->get();

        $disbursementQueue = Disbursement::with(['personalLoan.member'])
            ->where('loan_type', 1)
            ->whereRaw(
                "DATE({$disbursementDateExpression}) BETWEEN ? AND ?",
                [$monthStart->toDateString(), $monthEnd->toDateString()]
            )
            ->select('disbursements.*')
            ->selectRaw("{$disbursementDateExpression} as activity_date")
            ->orderByRaw("{$disbursementDateExpression} DESC")
            ->limit(6)
            ->get();

        $cashReceived = (clone $successfulRepaymentsBase)
            ->where(function ($query) {
                $query->where('type', 1)
                    ->orWhere('platform', 'cash');
            })
            ->sum('amount');

        $bankReceived = (clone $successfulRepaymentsBase)
            ->where(function ($query) {
                $query->where('type', 3)
                    ->orWhereIn('platform', ['bank', 'bank_transfer']);
            })
            ->sum('amount');

        $mobileReceived = (clone $successfulRepaymentsBase)
            ->where(function ($query) {
                $query->where('type', 2)
                    ->orWhere('platform', 'mobile');
            })
            ->sum('amount');

        $cashPaidOut = (clone $disbursementsBase)
            ->where('payment_type', 1)
            ->sum('amount');

        // Keep dashboard KPI definitions aligned:
        // - Total Collections = all successful repayments in month
        // - Total Paid Out = all disbursements in month
        // - Expected Balance = Total Collections - Total Paid Out
        $totalPaidOut = $disbursementsTodayAmount;
        $expectedBalance = $collectionsTodayAmount - $totalPaidOut;

        $activeCandidates = PersonalLoan::query()
            ->whereIn('status', [2, 3])
            ->with([
                'schedules',
                'repayments',
                'member:id,fname,lname,contact',
                'assignedTo:id,name',
                'approvedBy:id,name',
                'addedBy:id,name'
            ])
            ->get();

        $activeLoanCollection = $activeCandidates->filter(function ($loan) {
            $actualStatus = $loan->getActualStatus();
            $schedules = $loan->schedules ?? collect();

            if ($actualStatus !== 'running' || $schedules->isEmpty()) {
                return false;
            }

            $unpaidCount = $schedules->where('status', '!=', 1)->count();
            if ($unpaidCount <= 0) {
                return false;
            }

            $totalPayable = $schedules->sum(function ($schedule) {
                return (float) ($schedule->principal ?? 0) + (float) ($schedule->interest ?? 0);
            });

            $totalPaid = (float) ($loan->repayments ? $loan->repayments->where('status', 1)->sum('amount') : 0);
            $outstanding = $totalPayable - $totalPaid;

            return $outstanding != 0;
        })->values();

        $activeLoanIds = $activeLoanCollection->pluck('id');
        $activeLoanIdsArray = $activeLoanIds->toArray();
        $activeLoans = $activeLoanCollection->count();
        $totalLoansCount = PersonalLoan::count();

        $officerOptions = $activeLoanCollection
            ->map(function ($loan) {
                $officerId = $loan->assigned_to ?: ($loan->approved_by ?: $loan->added_by);
                $officerName = optional($loan->assignedTo)->name
                    ?: (optional($loan->approvedBy)->name ?: (optional($loan->addedBy)->name ?: 'Unassigned'));

                if (!$officerId) {
                    return null;
                }

                return [
                    'id' => (int) $officerId,
                    'name' => $officerName,
                ];
            })
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $parBuckets = [
            'par_1_30' => 0,
            'par_31_60' => 0,
            'par_61_90' => 0,
            'par_90_plus' => 0,
        ];

        $todayDate = now()->toDateString();
        $severeOverdueDate = now()->copy()->subDays(30)->toDateString();

        $today = now()->startOfDay();
        $officerStats = [];
        $clientsDueTodayRows = [];
        $badlyOverdueRows = [];

        foreach ($activeLoanCollection as $loan) {
            $officerId = $loan->assigned_to ?: ($loan->approved_by ?: $loan->added_by);
            $officerName = optional($loan->assignedTo)->name
                ?: (optional($loan->approvedBy)->name ?: (optional($loan->addedBy)->name ?: 'Unassigned'));

            if ($selectedOfficer && (int) $officerId !== $selectedOfficer) {
                continue;
            }

            $officerKey = $officerId ? ('id_' . $officerId) : 'unassigned';

            if (!isset($officerStats[$officerKey])) {
                $officerStats[$officerKey] = [
                    'officer_id' => $officerId,
                    'officer_name' => $officerName,
                    'assigned_loans' => 0,
                    'due_today_count' => 0,
                    'overdue_count' => 0,
                    'severe_overdue_count' => 0,
                    'collected_amount' => 0,
                ];
            }
            $officerStats[$officerKey]['assigned_loans']++;

            $loanHasDueToday = false;
            $loanHasOverdue = false;
            $loanHasSevereOverdue = false;

            foreach (($loan->schedules ?? collect()) as $schedule) {
                if ((int) ($schedule->status ?? 0) === 1) {
                    continue;
                }

                $dueDate = $this->parsePreviewDate($schedule->payment_date ?? null);
                if (!$dueDate) {
                    continue;
                }

                if ($dueDate->lt($today)) {
                    $daysOverdue = $dueDate->diffInDays($today);
                    $loanHasOverdue = true;

                    if ($daysOverdue <= 30) {
                        $parBuckets['par_1_30']++;
                    } elseif ($daysOverdue <= 60) {
                        $parBuckets['par_31_60']++;
                    } elseif ($daysOverdue <= 90) {
                        $parBuckets['par_61_90']++;
                    } else {
                        $parBuckets['par_90_plus']++;
                    }

                    if ($daysOverdue >= 30) {
                        $loanHasSevereOverdue = true;
                        $badlyOverdueRows[] = [
                            'schedule_id' => $schedule->id,
                            'loan_id' => $loan->id,
                            'loan_code' => $loan->code,
                            'fname' => optional($loan->member)->fname,
                            'lname' => optional($loan->member)->lname,
                            'contact' => optional($loan->member)->contact,
                            'officer_name' => $officerName,
                            'payment' => (float) ($schedule->payment ?? 0),
                            'payment_date' => $schedule->payment_date,
                            'days_overdue' => $daysOverdue,
                        ];
                    }
                }

                if ($dueDate->eq($today)) {
                    $loanHasDueToday = true;
                    $clientsDueTodayRows[] = [
                        'schedule_id' => $schedule->id,
                        'loan_id' => $loan->id,
                        'loan_code' => $loan->code,
                        'fname' => optional($loan->member)->fname,
                        'lname' => optional($loan->member)->lname,
                        'contact' => optional($loan->member)->contact,
                        'officer_name' => $officerName,
                        'payment' => (float) ($schedule->payment ?? 0),
                        'payment_date' => $schedule->payment_date,
                    ];
                }
            }

            if ($loanHasDueToday) {
                $officerStats[$officerKey]['due_today_count']++;
            }
            if ($loanHasOverdue) {
                $officerStats[$officerKey]['overdue_count']++;
            }
            if ($loanHasSevereOverdue) {
                $officerStats[$officerKey]['severe_overdue_count']++;
            }
        }

        $collectionsByOfficer = collect();
        if (!empty($activeLoanIdsArray)) {
            $collectionsByOfficer = DB::table('repayments as r')
                ->join('loan_schedules as ls', 'ls.id', '=', 'r.schedule_id')
                ->join('personal_loans as pl', 'pl.id', '=', 'ls.loan_id')
                ->whereIn('pl.id', $activeLoanIdsArray)
                ->where('r.amount', '>', 0)
                ->whereBetween('r.date_created', [$monthStart, $monthEnd])
                ->where(function ($query) {
                    $query->where('r.status', 1)
                        ->orWhere('r.payment_status', 'Completed');
                })
                ->groupBy(DB::raw('COALESCE(pl.assigned_to, pl.approved_by, pl.added_by)'))
                ->selectRaw('COALESCE(pl.assigned_to, pl.approved_by, pl.added_by) as officer_id, SUM(r.amount) as collected_amount')
                ->get()
                ->pluck('collected_amount', 'officer_id');
        }

        foreach ($officerStats as $key => $row) {
            $officerStats[$key]['collected_amount'] = (float) ($collectionsByOfficer[$row['officer_id']] ?? 0);
        }

        $officerPerformance = collect(array_values($officerStats))
            ->sortByDesc('overdue_count')
            ->take(10)
            ->values();

        $clientsDueToday = collect($clientsDueTodayRows)
            ->map(function ($row) {
                return (object) $row;
            })
            ->sortBy(['officer_name', 'payment_date'])
            ->take(20)
            ->values();

        $badlyOverdueClients = collect($badlyOverdueRows)
            ->map(function ($row) {
                return (object) $row;
            })
            ->sortByDesc('days_overdue')
            ->take(20)
            ->values();

        $recentActivity = collect();

        foreach ($collectionsQueue->take(3) as $item) {
            $loanCode = optional(optional($item->schedule)->personalLoan)->code ?? ('LN-' . $item->loan_id);
            $recentActivity->push([
                'time' => Carbon::parse($item->date_created),
                'text' => 'Repayment posted on ' . $loanCode . ' (UGX ' . number_format((float) $item->amount, 0) . ')',
            ]);
        }

        foreach ($disbursementQueue->take(3) as $item) {
            $loanCode = optional($item->personalLoan)->code ?? ('LN-' . $item->loan_id);
            $recentActivity->push([
                'time' => Carbon::parse($item->activity_date ?? $item->created_at ?? now()),
                'text' => 'Disbursement ' . strtolower($item->status_name ?? 'updated') . ' for ' . $loanCode,
            ]);
        }

        $recentActivity = $recentActivity
            ->sortByDesc('time')
            ->take(6)
            ->values();

        return view('admin.loans.personal-preview-dashboard', compact(
            'selectedMonth',
            'selectedOfficer',
            'officerOptions',
            'monthLabel',
            'collectionsTodayAmount',
            'collectionsTodayCount',
            'disbursementsTodayAmount',
            'disbursementsTodayCount',
            'pendingApprovals',
            'exceptionsCount',
            'cashReceived',
            'bankReceived',
            'mobileReceived',
            'cashPaidOut',
            'totalPaidOut',
            'expectedBalance',
            'totalLoansCount',
            'activeLoans',
            'parBuckets',
            'collectionsQueue',
            'disbursementQueue',
            'recentActivity',
            'officerPerformance',
            'clientsDueToday',
            'badlyOverdueClients'
        ));
    }

    /**
     * Parse legacy schedule date values safely.
     */
    private function parsePreviewDate($dateValue): ?Carbon
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', (string) $dateValue, $matches)) {
                return Carbon::createFromDate((int) $matches[3], (int) $matches[2], (int) $matches[1])->startOfDay();
            }

            return Carbon::parse($dateValue)->startOfDay();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Get loan by type and ID
     */
    private function getLoanByTypeAndId(string $type, int $id)
    {
        switch ($type) {
            case 'personal':
                return PersonalLoan::find($id);
            case 'group':
                return GroupLoan::find($id);
            case 'unified':
                return Loan::find($id);
            default:
                return null;
        }
    }
}