<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Loan;
use App\Models\Saving;
use App\Models\Repayment;
use App\Models\SavingTransaction;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard
     */
    public function index()
    {
        // Key Performance Indicators
        $kpis = [
            'total_members' => Member::count(),
            'active_members' => Member::where('status', 1)->count(),
            'total_loans' => Loan::count(),
            'active_loans' => Loan::where('status', 2)->count(),
            'total_savings' => Saving::count(),
            'active_savings' => Saving::where('status', 1)->count(),
            'total_groups' => Group::count(),
            'active_groups' => Group::where('status', 1)->count(),
        ];

        // Financial Overview
        $financials = [
            'total_disbursed' => Loan::where('status', 2)->sum('principal'),
            'total_outstanding' => Loan::where('status', 2)->sum('balance'),
            'total_repaid' => Repayment::sum('amount'),
            'total_savings_balance' => Saving::where('status', 1)->sum('balance'),
            'portfolio_at_risk' => $this->calculatePortfolioAtRisk(),
            'collection_rate' => $this->calculateCollectionRate(),
        ];

        // Recent Activities
        $recentLoans = Loan::with(['member', 'product', 'branch'])
                          ->latest()
                          ->take(5)
                          ->get();

        $recentRepayments = Repayment::with(['loan.member'])
                                   ->latest()
                                   ->take(5)
                                   ->get();

        $recentMembers = Member::with('branch')
                              ->latest()
                              ->take(5)
                              ->get();

        // Monthly Trends (Last 12 months)
        $monthlyTrends = $this->getMonthlyTrends();

        // Branch Performance
        $branchPerformance = Branch::withCount(['members', 'loans', 'savings'])
                                  ->with(['loans' => function($query) {
                                      $query->where('status', 2);
                                  }])
                                  ->get()
                                  ->map(function($branch) {
                                      return [
                                          'name' => $branch->name,
                                          'members' => $branch->members_count,
                                          'loans' => $branch->loans_count,
                                          'savings' => $branch->savings_count,
                                          'disbursed' => $branch->loans->sum('principal'),
                                      ];
                                  });

        // Product Performance
        $productPerformance = Product::withCount(['loans', 'savings'])
                                    ->get()
                                    ->map(function($product) {
                                        $disbursed = 0;
                                        $outstanding = 0;
                                        
                                        if ($product->type === 'loan') {
                                            $disbursed = Loan::where('product_type', $product->id)
                                                           ->where('status', 2)
                                                           ->sum('principal');
                                            $outstanding = Loan::where('product_type', $product->id)
                                                             ->where('status', 2)
                                                             ->sum('balance');
                                        } else {
                                            $disbursed = Saving::where('product_id', $product->id)
                                                             ->where('status', 1)
                                                             ->sum('balance');
                                        }

                                        return [
                                            'name' => $product->name,
                                            'type' => $product->type,
                                            'applications' => $product->type === 'loan' ? $product->loans_count : $product->savings_count,
                                            'disbursed' => $disbursed,
                                            'outstanding' => $outstanding,
                                        ];
                                    });

        // Alerts and Notifications
        $alerts = $this->getSystemAlerts();

        return view('admin.dashboard', compact(
            'kpis', 'financials', 'recentLoans', 'recentRepayments', 'recentMembers',
            'monthlyTrends', 'branchPerformance', 'productPerformance', 'alerts'
        ));
    }

    /**
     * Calculate Portfolio at Risk (PAR)
     */
    private function calculatePortfolioAtRisk()
    {
        $totalOutstanding = Loan::where('status', 2)->sum('balance');
        
        if ($totalOutstanding == 0) {
            return 0;
        }

        // Get loans with overdue payments (more than 30 days)
        $overdueLoans = Loan::where('status', 2)
                           ->whereHas('schedules', function($query) {
                               $query->where('status', 0) // Unpaid
                                     ->where('payment_date', '<', now()->subDays(30));
                           })
                           ->sum('balance');

        return round(($overdueLoans / $totalOutstanding) * 100, 2);
    }

    /**
     * Calculate Collection Rate
     */
    private function calculateCollectionRate()
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $expectedRepayments = DB::table('loan_schedules')
                               ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                               ->sum('payment');

        $actualRepayments = Repayment::whereBetween('date', [$startOfMonth, $endOfMonth])
                                   ->sum('amount');

        if ($expectedRepayments == 0) {
            return 100;
        }

        return round(($actualRepayments / $expectedRepayments) * 100, 2);
    }

    /**
     * Get monthly trends for the last 12 months
     */
    private function getMonthlyTrends()
    {
        $trends = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $trends[] = [
                'month' => $month->format('M Y'),
                'new_members' => Member::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
                'loans_disbursed' => Loan::whereBetween('created_at', [$startOfMonth, $endOfMonth])
                                        ->where('status', 2)
                                        ->sum('principal'),
                'repayments' => Repayment::whereBetween('date', [$startOfMonth, $endOfMonth])
                                       ->sum('amount'),
                'savings_deposits' => SavingTransaction::whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                                                     ->where('type', 'deposit')
                                                     ->sum('amount'),
            ];
        }

        return $trends;
    }

    /**
     * Get system alerts and notifications
     */
    private function getSystemAlerts()
    {
        $alerts = [];

        // Overdue loans
        $overdueLoans = Loan::where('status', 2)
                           ->whereHas('schedules', function($query) {
                               $query->where('status', 0)
                                     ->where('payment_date', '<', now());
                           })
                           ->count();

        if ($overdueLoans > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Overdue Loans',
                'message' => "{$overdueLoans} loans have overdue payments.",
                'action_url' => route('admin.loans.index', ['status' => 'overdue']),
                'action_text' => 'View Overdue Loans'
            ];
        }

        // Pending loan applications
        $pendingLoans = Loan::where('status', 0)->count();
        if ($pendingLoans > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Pending Applications',
                'message' => "{$pendingLoans} loan applications are pending approval.",
                'action_url' => route('admin.loans.index', ['status' => 0]),
                'action_text' => 'Review Applications'
            ];
        }

        // Low performing branches
        $lowPerformingBranches = Branch::whereHas('loans', function($query) {
            $query->where('status', 2)
                  ->whereHas('schedules', function($scheduleQuery) {
                      $scheduleQuery->where('status', 0)
                                    ->where('payment_date', '<', now()->subDays(30));
                  });
        })->count();

        if ($lowPerformingBranches > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Branch Performance',
                'message' => "{$lowPerformingBranches} branches have high Portfolio at Risk.",
                'action_url' => route('admin.branches.index'),
                'action_text' => 'View Branches'
            ];
        }

        // System health checks
        $inactiveMembers = Member::where('status', 0)->count();
        if ($inactiveMembers > 10) {
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Inactive Members',
                'message' => "{$inactiveMembers} members are inactive.",
                'action_url' => route('admin.members.index', ['status' => 0]),
                'action_text' => 'Review Members'
            ];
        }

        return $alerts;
    }

    /**
     * Get financial summary for a specific period
     */
    public function financialSummary(Request $request)
    {
        $startDate = $request->start_date ?? now()->startOfMonth();
        $endDate = $request->end_date ?? now()->endOfMonth();

        $summary = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1
            ],
            'loans' => [
                'applications' => Loan::whereBetween('created_at', [$startDate, $endDate])->count(),
                'approvals' => Loan::whereBetween('created_at', [$startDate, $endDate])
                                  ->where('status', '>=', 1)->count(),
                'disbursements' => Loan::whereBetween('created_at', [$startDate, $endDate])
                                      ->where('status', 2)->sum('principal'),
                'repayments' => Repayment::whereBetween('date', [$startDate, $endDate])->sum('amount'),
            ],
            'savings' => [
                'new_accounts' => Saving::whereBetween('created_at', [$startDate, $endDate])->count(),
                'deposits' => SavingTransaction::whereBetween('transaction_date', [$startDate, $endDate])
                                              ->where('type', 'deposit')->sum('amount'),
                'withdrawals' => SavingTransaction::whereBetween('transaction_date', [$startDate, $endDate])
                                                 ->where('type', 'withdrawal')->sum('amount'),
                'net_savings' => SavingTransaction::whereBetween('transaction_date', [$startDate, $endDate])
                                                 ->where('type', 'deposit')->sum('amount') -
                                SavingTransaction::whereBetween('transaction_date', [$startDate, $endDate])
                                                 ->where('type', 'withdrawal')->sum('amount'),
            ],
            'members' => [
                'new_registrations' => Member::whereBetween('created_at', [$startDate, $endDate])->count(),
                'verifications' => Member::whereBetween('updated_at', [$startDate, $endDate])
                                        ->where('verified', true)->count(),
            ]
        ];

        if ($request->ajax()) {
            return response()->json(['success' => true, 'summary' => $summary]);
        }

        return view('admin.reports.financial-summary', compact('summary'));
    }
}