<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ExportsData;
use App\Models\Loan;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Member;
use App\Models\Repayment;
use App\Models\Disbursement;
use App\Models\LoanCharge;
use App\Models\CashSecurity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportsController extends Controller
{
    use ExportsData;
    /**
     * Display pending loan applications report
     */
    public function pendingLoans(Request $request)
    {
        $data = [
            'loans' => collect(),
            'title' => 'Pending Loans Report',
            'rep_msg' => 'Please select a <strong>Date Range</strong> and <strong>Type</strong> to run the report.'
        ];

        if ($request->has('run')) {
            $startDate = $request->s_date;
            $endDate = $request->e_date;
            $type = $request->type;

            $loans = collect(); // Initialize the variable
            $typeLabel = 'Unknown';

            if ($type == '1') {
                // Personal loans
                $query = PersonalLoan::query()
                    ->where('verified', 0) // Pending
                    ->whereBetween('datecreated', [
                        Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d'),
                        Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d')
                    ]);

                $loans = $query->with(['member', 'branch', 'product'])->get();
                $typeLabel = 'Personal';
            } elseif ($type == '2') {
                // Group loans
                $query = GroupLoan::query()
                    ->where('verified', 0) // Pending
                    ->whereBetween('datecreated', [
                        Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d'),
                        Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d')
                    ]);

                $loans = $query->with(['group', 'branch', 'product'])->get();
                $typeLabel = 'Group';
            }

            $data['loans'] = $loans;
            $data['rep_msg'] = "{$typeLabel} Pending Loans Report from " . 
                Carbon::createFromFormat('d/m/Y', $startDate)->format('d/m/Y') . " to " . 
                Carbon::createFromFormat('d/m/Y', $endDate)->format('d/m/Y');
                
            // Handle exports
            if ($request->has('download')) {
                return $this->handleExport($request->download, $loans, 'pending_loans_' . $startDate . '_to_' . $endDate, "{$typeLabel} Pending Loans Report", 'pending_loans');
            }
        }
        
        $stats = [
            'total_pending' => PersonalLoan::where('verified', 0)->count() + GroupLoan::where('verified', 0)->count(),
            'total_amount' => PersonalLoan::where('verified', 0)->sum('principal') + GroupLoan::where('verified', 0)->sum('principal'),
            'today_applications' => PersonalLoan::where('verified', 0)->whereDate('datecreated', today())->count() + 
                                   GroupLoan::where('verified', 0)->whereDate('datecreated', today())->count(),
            'this_week' => PersonalLoan::where('verified', 0)->whereBetween('datecreated', [now()->startOfWeek(), now()->endOfWeek()])->count() +
                          GroupLoan::where('verified', 0)->whereBetween('datecreated', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => PersonalLoan::where('verified', 0)->whereMonth('datecreated', now()->month)->count() +
                           GroupLoan::where('verified', 0)->whereMonth('datecreated', now()->month)->count(),
        ];

        return view('admin.reports.pending-loans', compact('data', 'stats'));
    }

    /**
     * Display disbursed loans report
     */
    public function disbursedLoans(Request $request)
    {
        $data = [
            'loans' => collect(),
            'title' => 'Disbursed Loans Report',
            'rep_msg' => 'Please select a <strong>Date Range</strong> and <strong>Type</strong> to run the report.'
        ];

        if ($request->has('run')) {
            $startDate = $request->s_date;
            $endDate = $request->e_date;
            $type = $request->type;

            $loans = collect(); // Initialize the variable
            $typeLabel = 'Unknown';

            try {
                if ($type == '1') {
                    // Personal loans
                    $query = PersonalLoan::query()
                        ->where('verified', 1) // Disbursed
                        ->whereBetween('datecreated', [
                            Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d'),
                            Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d')
                        ]);

                    $loans = $query->with([
                        'member',
                        'branch',
                        'product',
                        'disbursements.investment'
                    ])->get();

                    $typeLabel = 'Personal';
                } elseif ($type == '2') {
                    // Group loans  
                    $query = GroupLoan::query()
                        ->where('verified', 1) // Disbursed
                        ->whereBetween('datecreated', [
                            Carbon::createFromFormat('d/m/Y', $startDate)->format('Y-m-d'),
                            Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d')
                        ]);

                    $loans = $query->with([
                        'group',
                        'branch', 
                        'product',
                        'disbursements.investment'
                    ])->get();

                    $typeLabel = 'Group';
                }

                // Transform data to match old system format
                $transformedLoans = $loans->map(function ($loan) use ($type) {
                    $disbursement = $loan->disbursements->where('status', 2)->first();
                    $memberName = $type == '1' ? 
                        ($loan->member ? $loan->member->fname . ' ' . $loan->member->lname : 'N/A') :
                        ($loan->group ? $loan->group->name : 'N/A');
                    
                    return (object) [
                        'id' => $loan->id,
                        'loan_type' => $type == '1' ? 'Personal Loan' : 'Group Loan',
                        // For view display (original names)
                        'mname' => $memberName,
                        'branch_name' => $loan->branch ? $loan->branch->name : 'N/A',
                        'code' => $loan->code,
                        'interest' => $loan->interest,
                        'period' => $loan->period,
                        'period_type' => $loan->product ? $loan->product->period_type : 2, // Default to months
                        'principal' => $loan->principal,
                        'inv_name' => $disbursement && $disbursement->investment ? 
                            $disbursement->investment->name : 'N/A',
                        'product_type' => $loan->product_type,
                        'member_id' => $type == '1' ? $loan->member_id : null,
                        'group_id' => $type == '2' ? $loan->group_id : null,
                        'outstanding_amount' => $loan->outstanding_amount ?? 0,
                        'due_date' => null, // Will calculate this based on disbursement date + period
                        'datecreated' => $loan->datecreated,
                        // For export (expected names)
                        'member_name' => $memberName,
                        'loan_code' => $loan->code,
                        'amount' => $loan->principal,
                        'date_disbursed' => $loan->datecreated,
                        'date_created' => $loan->datecreated,
                    ];
                });

                $data['loans'] = $transformedLoans;
                $data['rep_msg'] = "{$typeLabel} Disbursed Loans Report from " . 
                    Carbon::createFromFormat('d/m/Y', $startDate)->format('d/m/Y') . " to " . 
                    Carbon::createFromFormat('d/m/Y', $endDate)->format('d/m/Y');
                $data['header_title'] = $data['rep_msg'];
            } catch (\Exception $e) {
                $data['rep_msg'] = 'Error generating report: ' . $e->getMessage();
            }
        }

        // Handle exports
        if ($request->has('download') && isset($data['loans'])) {
            $typeLabel = isset($typeLabel) ? $typeLabel : 'All';
            return $this->handleExport($request->download, $data['loans'], 'disbursed_loans_' . date('Y-m-d'), "{$typeLabel} Disbursed Loans Report", 'disbursed_loans');
        }

        $stats = [
            'total_disbursed' => PersonalLoan::where('verified', 1)->count() + GroupLoan::where('verified', 1)->count(),
            'personal_loans' => PersonalLoan::where('verified', 1)->count(),
            'group_loans' => GroupLoan::where('verified', 1)->count(),
            'total_amount' => PersonalLoan::where('verified', 1)->sum('principal') + GroupLoan::where('verified', 1)->sum('principal'),
            'outstanding_amount' => PersonalLoan::where('verified', 1)->sum('principal') + GroupLoan::where('verified', 1)->sum('principal') - Repayment::sum('amount'),
            'today_disbursed' => PersonalLoan::where('verified', 1)->whereDate('datecreated', today())->count() + 
                               GroupLoan::where('verified', 1)->whereDate('datecreated', today())->count(),
            'this_week' => PersonalLoan::where('verified', 1)->whereBetween('datecreated', [now()->startOfWeek(), now()->endOfWeek()])->count() +
                          GroupLoan::where('verified', 1)->whereBetween('datecreated', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => PersonalLoan::where('verified', 1)->whereMonth('datecreated', now()->month)->count() +
                           GroupLoan::where('verified', 1)->whereMonth('datecreated', now()->month)->count(),
        ];

        return view('admin.reports.disbursed_loans', compact('data', 'stats'));
    }

    /**
     * Display rejected loans report
     */
    public function rejectedLoans(Request $request)
    {
        // Get rejected personal loans
        $personalQuery = PersonalLoan::with(['member', 'branch'])
            ->where('verified', 2); // Rejected status

        // Get rejected group loans  
        $groupQuery = GroupLoan::with(['group', 'branch'])
            ->where('verified', 2); // Rejected status

        // Apply date filters
        if ($request->has('start_date') && !empty($request->start_date)) {
            $personalQuery->whereDate('datecreated', '>=', $request->start_date);
            $groupQuery->whereDate('datecreated', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $personalQuery->whereDate('datecreated', '<=', $request->end_date);
            $groupQuery->whereDate('datecreated', '<=', $request->end_date);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $personalQuery->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhereHas('member', function ($q2) use ($search) {
                      $q2->where('fname', 'LIKE', "%{$search}%")
                         ->orWhere('lname', 'LIKE', "%{$search}%");
                  });
            });
            $groupQuery->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhereHas('group', function ($q2) use ($search) {
                      $q2->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Apply branch filter
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $personalQuery->where('branch_id', $request->branch_id);
            $groupQuery->where('branch_id', $request->branch_id);
        }

        // Apply loan type filter
        $loans = collect();
        if ($request->has('loan_type') && !empty($request->loan_type)) {
            if ($request->loan_type === 'Personal Loan') {
                $loans = $personalQuery->get()->map(function ($loan) {
                    $loan->loan_type = 'Personal Loan';
                    return $loan;
                });
            } elseif ($request->loan_type === 'Group Loan') {
                $loans = $groupQuery->get()->map(function ($loan) {
                    $loan->loan_type = 'Group Loan';
                    return $loan;
                });
            }
        } else {
            // Get both types
            $personalLoans = $personalQuery->get()->map(function ($loan) {
                $loan->loan_type = 'Personal Loan';
                return $loan;
            });
            $groupLoans = $groupQuery->get()->map(function ($loan) {
                $loan->loan_type = 'Group Loan';
                return $loan;
            });
            $loans = $personalLoans->concat($groupLoans);
        }

        $data = [
            'loans' => $loans,
            'title' => 'Rejected Loans Report',
            'rep_msg' => 'Rejected loan applications with filtering options'
        ];
        
        // Handle exports
        if ($request->has('download')) {
            return $this->handleExport($request->download, $loans, 'rejected_loans_' . date('Y-m-d'), 'Rejected Loans Report', 'rejected_loans');
        }
        
        $stats = [
            'total_rejected' => PersonalLoan::where('verified', 2)->count() + GroupLoan::where('verified', 2)->count(),
            'rejected_amount' => PersonalLoan::where('verified', 2)->sum('principal') + GroupLoan::where('verified', 2)->sum('principal'),
            'today_rejected' => PersonalLoan::where('verified', 2)->whereDate('datecreated', today())->count() + 
                               GroupLoan::where('verified', 2)->whereDate('datecreated', today())->count(),
            'this_week' => PersonalLoan::where('verified', 2)->whereBetween('datecreated', [now()->startOfWeek(), now()->endOfWeek()])->count() +
                          GroupLoan::where('verified', 2)->whereBetween('datecreated', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => PersonalLoan::where('verified', 2)->whereMonth('datecreated', now()->month)->count() +
                           GroupLoan::where('verified', 2)->whereMonth('datecreated', now()->month)->count(),
        ];

        return view('admin.reports.rejected-loans', compact('data', 'stats'));
    }

    /**
     * Display loans due report (loans with pending repayments)
     */
    public function loansDue(Request $request)
    {
        $data = [
            'loans' => collect(),
            'title' => 'Loans Due Report',
            'rep_msg' => 'Please select a <strong>Date</strong> to run the report.'
        ];

        if ($request->has('run') && $request->has('date')) {
            $selectedDate = $request->date;
            
            try {
                // Convert the date to the format used in the database (dd-mm-yyyy)
                $targetDate = Carbon::createFromFormat('Y-m-d', $selectedDate)->format('d-m-Y');
                
                // Get personal loan schedules
                $personalLoans = DB::table('loan_schedules as s')
                    ->join('personal_loans as p', 's.loan_id', '=', 'p.id')
                    ->leftJoin('members as m', 'p.member_id', '=', 'm.id')
                    ->leftJoin('branches as b', 'p.branch_id', '=', 'b.id')
                    ->where('s.status', '0') // Unpaid
                    ->where('p.verified', '1') // Verified loans only
                    ->where('s.payment_date', $targetDate)
                    ->select(
                        's.*',
                        'p.id as loan_id',
                        'p.code as loan_code',
                        'p.member_id',
                        'p.period',
                        'p.principal as loan_amount',
                        DB::raw("CONCAT(COALESCE(m.fname, ''), ' ', COALESCE(m.lname, '')) as member_name"),
                        'b.name as branch_name',
                        's.payment as installment_amount',
                        's.principal as principal_amount',
                        's.interest as interest_amount',
                        's.payment_date as expected_date',
                        DB::raw("'Personal Loan' as loan_type")
                    )
                    ->get();

                // Get group loan schedules  
                $groupLoans = DB::table('group_loan_schedules as s')
                    ->join('group_loans as p', 's.loan_id', '=', 'p.id')
                    ->leftJoin('groups as g', 'p.group_id', '=', 'g.id')
                    ->leftJoin('branches as b', 'p.branch_id', '=', 'b.id')
                    ->where('s.status', '0') // Unpaid
                    ->where('p.verified', '1') // Verified loans only
                    ->where('s.payment_date', $targetDate)
                    ->select(
                        's.*',
                        'p.id as loan_id',
                        'p.code as loan_code', 
                        'p.group_id',
                        'p.period',
                        'p.principal as loan_amount',
                        'g.name as member_name',
                        'b.name as branch_name',
                        's.payment as installment_amount',
                        's.principal as principal_amount', 
                        's.interest as interest_amount',
                        's.payment_date as expected_date',
                        DB::raw("'Group Loan' as loan_type")
                    )
                    ->get();

                // Combine both collections
                $loans = $personalLoans->concat($groupLoans);
                
                $data['loans'] = $loans;
                $data['rep_msg'] = 'Loans due on <strong>' . Carbon::createFromFormat('Y-m-d', $selectedDate)->format('d/m/Y') . '</strong>';
                
                // Handle exports
                if ($request->has('download')) {
                    return $this->handleExport($request->download, $loans, 'loans_due_' . $selectedDate, 'Loans Due Report', 'loans_due');
                }
                
            } catch (\Exception $e) {
                $data['rep_msg'] = 'Error: ' . $e->getMessage();
            }
        }

        return view('admin.reports.loans-due', $data);
    }

    /**
     * Export Loans Due to CSV
     */
    private function exportLoansDueCSV($loans, $date)
    {
        $filename = 'loans_due_' . $date . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($loans) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'No.',
                'Member Name',
                'Branch',
                'Code',
                'Period',
                'Installment (UGX)',
                'Principal (UGX)',
                'Interest (UGX)',
                'Expected Date',
                'Loan Type'
            ]);

            // Add data rows
            foreach ($loans as $index => $loan) {
                fputcsv($file, [
                    $index + 1,
                    $loan->member_name ?? 'N/A',
                    $loan->branch_name ?? 'N/A',
                    $loan->loan_code ?? 'N/A',
                    $loan->period ?? 'N/A',
                    $loan->installment_amount ?? 0,
                    $loan->principal_amount ?? 0,
                    $loan->interest_amount ?? 0,
                    $loan->expected_date ?? 'N/A',
                    $loan->loan_type ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display paid loans report (fully repaid loans)
     */
    public function paidLoans(Request $request)
    {
        $data = [
            'loans' => collect(),
            'title' => 'Paid Loans Report',
            'rep_msg' => 'Please select a <strong>Date Range</strong> and <strong>Type</strong> to run the report.'
        ];

        if ($request->has('run') && $request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $type = $request->type ?? '';

            try {
                $loans = collect();

                if ($type === 'personal' || empty($type)) {
                    // Get personal loans that are fully paid
                    $personalLoans = DB::table('personal_loans as p')
                        ->leftJoin('members as m', 'p.member_id', '=', 'm.id')
                        ->leftJoin('branches as b', 'p.branch_id', '=', 'b.id')
                        ->where('p.status', '1') // Paid status
                        ->where('p.verified', '1') // Verified/Disbursed
                        ->whereNotNull('p.date_closed') // Must have a closed date
                        ->whereDate('p.date_closed', '>=', $startDate)
                        ->whereDate('p.date_closed', '<=', $endDate)
                        ->select(
                            'p.id as loan_id',
                            'p.code as loan_code',
                            'p.member_id',
                            'p.interest',
                            'p.period',
                            'p.principal as amount',
                            'p.principal as investment', // Investment = principal amount
                            'p.date_closed',
                            DB::raw("CONCAT(COALESCE(m.fname, ''), ' ', COALESCE(m.lname, '')) as member_name"),
                            'b.name as branch_name',
                            DB::raw("'Personal Loan' as loan_type")
                        )
                        ->get();

                    $loans = $loans->concat($personalLoans);
                }

                if ($type === 'group' || empty($type)) {
                    // Get group loans that are fully paid
                    $groupLoans = DB::table('group_loans as p')
                        ->leftJoin('groups as g', 'p.group_id', '=', 'g.id')
                        ->leftJoin('branches as b', 'p.branch_id', '=', 'b.id')
                        ->where('p.status', '1') // Paid status
                        ->where('p.verified', '1') // Verified/Disbursed
                        ->whereNotNull('p.date_closed') // Must have a closed date
                        ->whereDate('p.date_closed', '>=', $startDate)
                        ->whereDate('p.date_closed', '<=', $endDate)
                        ->select(
                            'p.id as loan_id',
                            'p.code as loan_code',
                            'p.group_id',
                            'p.interest',
                            'p.period',
                            'p.principal as amount',
                            'p.principal as investment', // Investment = principal amount
                            'p.date_closed',
                            'g.name as member_name',
                            'b.name as branch_name',
                            DB::raw("'Group Loan' as loan_type")
                        )
                        ->get();

                    $loans = $loans->concat($groupLoans);
                }

                $data['loans'] = $loans;
                
                if ($type === 'personal') {
                    $typeLabel = 'Personal';
                } elseif ($type === 'group') {
                    $typeLabel = 'Group';
                } else {
                    $typeLabel = 'All';
                }
                
                $data['rep_msg'] = "{$typeLabel} Paid Loans from <strong>" . Carbon::parse($startDate)->format('d/m/Y') . "</strong> to <strong>" . Carbon::parse($endDate)->format('d/m/Y') . "</strong>";
                
                // Handle exports
                if ($request->has('download')) {
                    return $this->handleExport($request->download, $loans, 'paid_loans_' . $startDate . '_to_' . $endDate, "{$typeLabel} Paid Loans Report", 'paid_loans');
                }
                
            } catch (\Exception $e) {
                $data['rep_msg'] = 'Error: ' . $e->getMessage();
            }
        }

        return view('admin.reports.paid-loans', $data);
    }

    /**
     * Export Paid Loans to CSV
     */
    private function exportPaidLoansCSV($loans, $startDate, $endDate, $typeLabel)
    {
        $filename = 'paid_loans_' . $startDate . '_to_' . $endDate . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($loans) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'No.',
                'Member Name',
                'Branch',
                'Code',
                'Interest',
                'Period',
                'Amount (UGX)',
                'Date Closed',
                'Investment (UGX)',
                'Loan Type'
            ]);

            // Add data rows
            foreach ($loans as $index => $loan) {
                fputcsv($file, [
                    $index + 1,
                    $loan->member_name ?? 'N/A',
                    $loan->branch_name ?? 'N/A',
                    $loan->loan_code ?? 'N/A',
                    $loan->interest ?? 'N/A',
                    $loan->period ?? 'N/A',
                    $loan->amount ?? 0,
                    $loan->date_closed ? Carbon::parse($loan->date_closed)->format('d/m/Y') : 'N/A',
                    $loan->investment ?? 0,
                    $loan->loan_type ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display loan repayments report
     */
    public function loanRepayments(Request $request)
    {
        $query = Repayment::with(['loan.member', 'loan.product', 'loan.branch']);

        // Apply date filters
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('date_created', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('date_created', '<=', $request->end_date);
        }

        // Filter by payment method (platform)
        if ($request->has('payment_method') && !empty($request->payment_method)) {
            $query->where('platform', $request->payment_method);
        }

        // Filter by amount range
        if ($request->has('amount_range') && !empty($request->amount_range)) {
            $range = $request->amount_range;
            if ($range === '0-100000') {
                $query->whereBetween('amount', [0, 100000]);
            } elseif ($range === '100000-500000') {
                $query->whereBetween('amount', [100000, 500000]);
            } elseif ($range === '500000-1000000') {
                $query->whereBetween('amount', [500000, 1000000]);
            } elseif ($range === '1000000+') {
                $query->where('amount', '>', 1000000);
            }
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('txn_id', 'LIKE', "%{$search}%")
                  ->orWhere('details', 'LIKE', "%{$search}%")
                  ->orWhere('id', 'LIKE', "%{$search}%")
                  ->orWhereHas('loan.member', function ($q2) use ($search) {
                      $q2->where('fname', 'LIKE', "%{$search}%")
                         ->orWhere('lname', 'LIKE', "%{$search}%");
                  });
            });
        }

        $payments = $query->orderBy('date_created', 'desc')->paginate(20);
        
        // Handle exports
        if ($request->has('download')) {
            $allPayments = $query->orderBy('date_created', 'desc')->get();
            return $this->handleExport($request->download, $allPayments, 'loan_repayments_' . date('Y-m-d'), 'Loan Repayments Report', 'loan_repayments');
        }
        
        $stats = [
            'total_payments' => Repayment::count(),
            'total_amount' => Repayment::sum('amount'),
            'today_payments' => Repayment::whereDate('date_created', today())->count(),
            'today_amount' => Repayment::whereDate('date_created', today())->sum('amount'),
            'this_month_amount' => Repayment::whereMonth('date_created', now()->month)->sum('amount'),
        ];

        return view('admin.reports.loan-repayments', compact('payments', 'stats'));
    }

    /**
     * Display payment transactions report
     */
    public function paymentTransactions(Request $request)
    {
        $query = Repayment::with(['loan.member', 'loan.product', 'loan.branch']);

        // Apply date filters
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('date_created', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('date_created', '<=', $request->end_date);
        }

        // Filter by payment method
        if ($request->has('payment_method') && !empty($request->payment_method)) {
            $query->where('type', $request->payment_method);
        }

        $payments = $query->orderBy('date_created', 'desc')->paginate(20);
        
        $stats = [
            'total_transactions' => Repayment::count(),
            'total_amount' => Repayment::sum('amount'),
            'today_transactions' => Repayment::whereDate('date_created', today())->count(),
            'today_amount' => Repayment::whereDate('date_created', today())->sum('amount'),
            'by_method' => Repayment::select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                                 ->groupBy('type')
                                 ->get(),
        ];

        return view('admin.reports.payment-transactions', compact('payments', 'stats'));
    }

    /**
     * Display loan interest report
     */
    public function loanInterest(Request $request)
    {
        // Focus on loans that have been disbursed
        $query = Loan::with(['member', 'product', 'branch', 'disbursements'])
                     ->whereHas('disbursements', function($q) {
                         $q->where('status', 2); // Successfully disbursed
                     });

        // Apply filters
        $this->applyFilters($query, $request);

        $loans = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $stats = [
            'total_disbursed_loans' => Loan::whereHas('disbursements', function($q) {
                $q->where('status', 2);
            })->count(),
            'total_principal_disbursed' => Disbursement::where('status', 2)->sum('amount'),
            'average_interest_rate' => Loan::whereHas('disbursements', function($q) {
                $q->where('status', 2);
            })->avg('interest'),
            'total_potential_interest' => Loan::whereHas('disbursements', function($q) {
                $q->where('status', 2);
            })->get()->sum(function($loan) {
                $disbursedAmount = $loan->disbursements->where('status', 2)->sum('amount');
                return ($disbursedAmount * $loan->interest / 100);
            }),
        ];

        return view('admin.reports.loan-interest', compact('loans', 'stats'));
    }

    /**
     * Display cash securities report
     */
    public function cashSecurities(Request $request)
    {
        $query = CashSecurity::with(['member', 'loan']);

        // Apply date filters
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $securities = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $stats = [
            'total_securities' => CashSecurity::count(),
            'total_amount' => CashSecurity::sum('amount'),
            'released_amount' => CashSecurity::where('status', 'released')->sum('amount'),
            'held_amount' => CashSecurity::where('status', 'held')->sum('amount'),
            'average_security' => CashSecurity::avg('amount'),
        ];

        return view('admin.reports.cash-securities', compact('securities', 'stats'));
    }

    /**
     * Display loan charges report
     */
    public function loanCharges(Request $request)
    {
        $query = LoanCharge::with(['loan.member', 'loan.product']);

        // Apply date filters
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by charge type
        if ($request->has('charge_type') && !empty($request->charge_type)) {
            $query->where('charge_type', $request->charge_type);
        }

        $charges = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $stats = [
            'total_charges' => LoanCharge::count(),
            'total_amount' => LoanCharge::sum('amount'),
            'paid_charges' => LoanCharge::where('status', 'paid')->sum('amount'),
            'pending_charges' => LoanCharge::where('status', 'pending')->sum('amount'),
            'by_type' => LoanCharge::select('charge_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                                  ->groupBy('charge_type')
                                  ->get(),
        ];

        return view('admin.reports.loan-charges', compact('charges', 'stats'));
    }

    /**
     * Apply common filters to queries
     */
    private function applyFilters($query, Request $request)
    {
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhereHas('member', function ($q2) use ($search) {
                      $q2->where('first_name', 'LIKE', "%{$search}%")
                         ->orWhere('last_name', 'LIKE', "%{$search}%")
                         ->orWhere('member_id', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Date range filtering
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Branch filtering
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
        }

        // Product filtering
        if ($request->has('product_id') && !empty($request->product_id)) {
            $query->where('product_type', $request->product_id);
        }
    }

    /**
     * Handle export for all reports
     */
    private function handleExport($format, $data, $filename, $title, $reportType)
    {
        $exportData = [];
        $headers = $this->getReportHeaders($reportType);
        
        foreach ($data as $index => $item) {
            $exportData[] = $this->formatReportRow($item, $index, $reportType);
        }
        
        switch ($format) {
            case 'excel':
                return $this->exportToExcel($exportData, $headers, $filename, $title);
            case 'pdf':
                return $this->exportToPdf($exportData, $headers, $filename, $title);
            case 'csv':
            default:
                return $this->exportToCsv($exportData, $headers, $filename);
        }
    }
    
    /**
     * Get headers for different report types
     */
    private function getReportHeaders($reportType)
    {
        switch ($reportType) {
            case 'paid_loans':
                return ['No.', 'Member Name', 'Branch', 'Code', 'Interest (%)', 'Period (Months)', 'Amount (UGX)', 'Date Closed', 'Investment (UGX)', 'Loan Type'];
            case 'loans_due':
                return ['No.', 'Member Name', 'Branch', 'Code', 'Period (Months)', 'Installment (UGX)', 'Principal (UGX)', 'Interest (UGX)', 'Expected Date', 'Loan Type'];
            case 'pending_loans':
                return ['No.', 'Member Name', 'Branch', 'Code', 'Amount (UGX)', 'Interest (%)', 'Period (Months)', 'Date Created', 'Loan Type'];
            case 'disbursed_loans':
                return ['No.', 'Member Name', 'Branch', 'Code', 'Amount (UGX)', 'Interest (%)', 'Period (Months)', 'Date Disbursed', 'Loan Type'];
            case 'rejected_loans':
                return ['No.', 'Member Name', 'Branch', 'Code', 'Amount (UGX)', 'Interest (%)', 'Period (Months)', 'Date Rejected', 'Reason', 'Loan Type'];
            case 'loan_repayments':
                return ['No.', 'Transaction ID', 'Platform', 'Date Created', 'First Name', 'Last Name', 'Phone', 'Amount (UGX)', 'Status', 'Loan Type'];
            case 'payment_transactions':
                return ['No.', 'Transaction ID', 'Member Name', 'Amount (UGX)', 'Type', 'Date', 'Status', 'Reference'];
            case 'loan_interest':
                return ['No.', 'Member Name', 'Branch', 'Code', 'Principal (UGX)', 'Interest Rate (%)', 'Interest Amount (UGX)', 'Date', 'Loan Type'];
            case 'cash_securities':
                return ['No.', 'Member Name', 'Branch', 'Amount (UGX)', 'Date Deposited', 'Status', 'Reference'];
            case 'loan_charges':
                return ['No.', 'Member Name', 'Branch', 'Loan Code', 'Charge Type', 'Amount (UGX)', 'Date Applied', 'Status'];
            default:
                return ['No.', 'Data'];
        }
    }
    
    /**
     * Format row data for different report types
     */
    private function formatReportRow($item, $index, $reportType)
    {
        switch ($reportType) {
            case 'paid_loans':
                return [
                    $index + 1,
                    $item->member_name ?? 'N/A',
                    $item->branch_name ?? 'N/A',
                    $item->loan_code ?? 'N/A',
                    $item->interest ?? 'N/A',
                    $item->period ?? 'N/A',
                    $item->amount ?? 0,
                    $item->date_closed ? Carbon::parse($item->date_closed)->format('d/m/Y') : 'N/A',
                    $item->investment ?? 0,
                    $item->loan_type ?? 'N/A'
                ];
            case 'loans_due':
                return [
                    $index + 1,
                    $item->member_name ?? 'N/A',
                    $item->branch_name ?? 'N/A',
                    $item->loan_code ?? 'N/A',
                    $item->period ?? 'N/A',
                    $item->installment_amount ?? 0,
                    $item->principal_amount ?? 0,
                    $item->interest_amount ?? 0,
                    $item->expected_date ?? 'N/A',
                    $item->loan_type ?? 'N/A'
                ];
            case 'pending_loans':
                return [
                    $index + 1,
                    $item->member_name ?? 'N/A',
                    $item->branch_name ?? 'N/A',
                    $item->loan_code ?? 'N/A',
                    $item->amount ?? 0,
                    $item->interest ?? 'N/A',
                    $item->period ?? 'N/A',
                    $item->date_created ? Carbon::parse($item->date_created)->format('d/m/Y') : 'N/A',
                    $item->loan_type ?? 'N/A'
                ];
            case 'disbursed_loans':
                return [
                    $index + 1,
                    $item->member_name ?? 'N/A',
                    $item->branch_name ?? 'N/A',
                    $item->loan_code ?? 'N/A',
                    $item->amount ?? 0,
                    $item->interest ?? 'N/A',
                    $item->period ?? 'N/A',
                    $item->date_disbursed ? Carbon::parse($item->date_disbursed)->format('d/m/Y') : 'N/A',
                    $item->loan_type ?? 'N/A'
                ];
            case 'rejected_loans':
                return [
                    $index + 1,
                    $item->member_name ?? 'N/A',
                    $item->branch_name ?? 'N/A',
                    $item->loan_code ?? 'N/A',
                    $item->amount ?? 0,
                    $item->interest ?? 'N/A',
                    $item->period ?? 'N/A',
                    $item->date_rejected ? Carbon::parse($item->date_rejected)->format('d/m/Y') : 'N/A',
                    $item->rejection_reason ?? 'N/A',
                    $item->loan_type ?? 'N/A'
                ];
            case 'loan_repayments':
                return [
                    $index + 1,
                    $item->txn_id ?? 'N/A',
                    $item->platform ?? 'N/A',
                    $item->date_created ? Carbon::parse($item->date_created)->format('d/m/Y') : 'N/A',
                    $item->fname ?? 'N/A',
                    $item->lname ?? 'N/A',
                    $item->phone ?? 'N/A',
                    $item->amount ?? 0,
                    $item->status ?? 'N/A',
                    $item->loan_type ?? 'N/A'
                ];
            default:
                return [$index + 1, 'N/A'];
        }
    }
}