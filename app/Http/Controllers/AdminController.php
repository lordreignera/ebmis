<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminController extends Controller
{
    /**
     * Detect which database schema is being used (old or new)
     * Returns table name and timestamp column to use
     */
    private function getLoansTableInfo()
    {
        // Check if personal_loans (old) or loans (new) table exists
        $tableName = Schema::hasTable('personal_loans') ? 'personal_loans' : 'loans';
        
        // Check if datecreated (old) or created_at (new) column exists
        $timestampColumn = Schema::hasColumn($tableName, 'datecreated') ? 'datecreated' : 'created_at';
        
        return ['table' => $tableName, 'timestamp' => $timestampColumn];
    }
    
    private function getDisbursementTableInfo()
    {
        $tableName = Schema::hasTable('disbursement') ? 'disbursement' : 'disbursements';
        $timestampColumn = Schema::hasColumn($tableName, 'datecreated') ? 'datecreated' : 'created_at';
        return ['table' => $tableName, 'timestamp' => $timestampColumn];
    }
    
    private function getTimestampColumn($tableName)
    {
        return Schema::hasColumn($tableName, 'datecreated') ? 'datecreated' : 'created_at';
    }

    public function home()
    {
        // Get current date
        $today = Carbon::today();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        // Detect which database schema we're using
        $loansInfo = $this->getLoansTableInfo();
        $disbursementInfo = $this->getDisbursementTableInfo();
        $groupLoansTimestamp = $this->getTimestampColumn('group_loans');
        $savingsTimestamp = $this->getTimestampColumn('savings');
        $investmentTimestamp = $this->getTimestampColumn('investment');

        // === MEMBERS STATISTICS ===
        $totalMembers = DB::table('members')
            ->where('member_type', '!=', '4') // Exclude investors
            ->count();

        $activatedMembers = DB::table('members')
            ->where('verified', '1')
            ->where('member_type', '!=', '4')
            ->count();

        $pendingMembers = DB::table('members')
            ->where('verified', '0')
            ->where('member_type', '!=', '4')
            ->count();

        // === GROUPS STATISTICS ===
        $totalGroups = DB::table('groups')
            ->count();

        // === PERSONAL LOANS STATISTICS ===
        $personalLoansCount = DB::table($loansInfo['table'])
            ->where('verified', '1')
            ->count();

        $personalLoansValue = DB::table($loansInfo['table'])
            ->where('verified', '1')
            ->sum('principal');

        $personalLoansThisMonth = DB::table($loansInfo['table'])
            ->whereMonth($loansInfo['timestamp'], $currentMonth)
            ->whereYear($loansInfo['timestamp'], $currentYear)
            ->where('verified', '1')
            ->sum('principal');

        $personalLoansCountThisMonth = DB::table($loansInfo['table'])
            ->whereMonth($loansInfo['timestamp'], $currentMonth)
            ->whereYear($loansInfo['timestamp'], $currentYear)
            ->where('verified', '1')
            ->count();

        // Active loans (status = 1)
        $activePersonalLoans = DB::table($loansInfo['table'])
            ->where('status', '1')
            ->count();

        // Outstanding amount - use principal for active loans
        $activePersonalLoansValue = DB::table($loansInfo['table'])
            ->where('status', '1')
            ->selectRaw('SUM(CAST(principal as DECIMAL(15,2))) as total_principal')
            ->value('total_principal') ?? 0;

        // === GROUP LOANS STATISTICS ===
        $groupLoansCount = DB::table('group_loans')
            ->where('verified', '1')
            ->count();

        $groupLoansValue = DB::table('group_loans')
            ->where('verified', '1')
            ->sum('principal');

        $groupLoansThisMonth = DB::table('group_loans')
            ->whereMonth($groupLoansTimestamp, $currentMonth)
            ->whereYear($groupLoansTimestamp, $currentYear)
            ->where('verified', '1')
            ->sum('principal');

        $groupLoansCountThisMonth = DB::table('group_loans')
            ->whereMonth($groupLoansTimestamp, $currentMonth)
            ->whereYear($groupLoansTimestamp, $currentYear)
            ->where('verified', '1')
            ->count();

        // Active group loans
        $activeGroupLoans = DB::table('group_loans')
            ->where('status', '1')
            ->count();

        // Outstanding amount for group loans - use principal for active loans
        $activeGroupLoansValue = DB::table('group_loans')
            ->where('status', '1')
            ->selectRaw('SUM(CAST(principal as DECIMAL(15,2))) as total_principal')
            ->value('total_principal') ?? 0;

        // === TOTAL LOANS (Personal + Group) ===
        $totalLoansCount = $personalLoansCount + $groupLoansCount;
        $totalLoansValue = $personalLoansValue + $groupLoansValue;
        $totalLoansThisMonth = $personalLoansThisMonth + $groupLoansThisMonth;
        $totalLoansCountThisMonth = $personalLoansCountThisMonth + $groupLoansCountThisMonth;
        $totalActiveLoans = $activePersonalLoans + $activeGroupLoans;
        $totalActiveLoansValue = $activePersonalLoansValue + $activeGroupLoansValue;

        // === REPAYMENTS DUE (Overdue) ===
        $repaymentsDue = DB::table('loan_schedules')
            ->where('status', '0')
            ->where('payment_date', '<', $today)
            ->sum('payment');

        $repaymentsDueCount = DB::table('loan_schedules')
            ->where('status', '0')
            ->where('payment_date', '<', $today)
            ->count();

        // === REPAYMENTS DUE TODAY ===
        $repaymentsDueToday = DB::table('loan_schedules')
            ->where('status', '0')
            ->whereDate('payment_date', $today)
            ->sum('payment');

        $repaymentsDueTodayCount = DB::table('loan_schedules')
            ->where('status', '0')
            ->whereDate('payment_date', $today)
            ->count();

        // === CASH SECURITIES (SAVINGS) ===
        $totalSavingsCount = DB::table('savings')
            ->count();

        $totalSavingsValue = DB::table('savings')
            ->sum('value');

        $savingsThisMonth = DB::table('savings')
            ->whereMonth($savingsTimestamp, $currentMonth)
            ->whereYear($savingsTimestamp, $currentYear)
            ->sum('value');

        // === INVESTMENTS ===
        $totalInvestors = DB::table('members')
            ->where('member_type', '4')
            ->count();

        $totalInvestmentValue = DB::table('investment')
            ->sum('amount');

        $investmentThisMonth = DB::table('investment')
            ->whereMonth($investmentTimestamp, $currentMonth)
            ->whereYear($investmentTimestamp, $currentYear)
            ->sum('amount');

        // === DISBURSEMENTS ===
        $disbursementsThisMonth = DB::table($disbursementInfo['table'])
            ->whereMonth($disbursementInfo['timestamp'], $currentMonth)
            ->whereYear($disbursementInfo['timestamp'], $currentYear)
            ->sum('amount');

        $disbursementsThisMonthCount = DB::table($disbursementInfo['table'])
            ->whereMonth($disbursementInfo['timestamp'], $currentMonth)
            ->whereYear($disbursementInfo['timestamp'], $currentYear)
            ->count();

        // === PENDING APPROVALS ===
        $pendingSignature = DB::table($loansInfo['table'])
            ->where('status', '0')
            ->count();

        $pendingApproval = DB::table($loansInfo['table'])
            ->where('status', '2')
            ->count();

        $pendingDisbursement = DB::table($loansInfo['table'])
            ->where('status', '3')
            ->count();

        // === COMPILE ALL STATS ===
        $stats = [
            // Members
            'total_members' => $totalMembers,
            'activated_members' => $activatedMembers,
            'pending_members' => $pendingMembers,
            'total_groups' => $totalGroups,

            // Loans - Personal
            'personal_loans_count' => $personalLoansCount,
            'personal_loans_value' => $personalLoansValue,
            'personal_loans_month' => $personalLoansThisMonth,
            'personal_loans_month_count' => $personalLoansCountThisMonth,
            'active_personal_loans' => $activePersonalLoans,
            'active_personal_loans_value' => $activePersonalLoansValue,

            // Loans - Group
            'group_loans_count' => $groupLoansCount,
            'group_loans_value' => $groupLoansValue,
            'group_loans_month' => $groupLoansThisMonth,
            'group_loans_month_count' => $groupLoansCountThisMonth,
            'active_group_loans' => $activeGroupLoans,
            'active_group_loans_value' => $activeGroupLoansValue,

            // Loans - Total
            'total_loans_count' => $totalLoansCount,
            'total_loans_value' => $totalLoansValue,
            'total_loans_month' => $totalLoansThisMonth,
            'total_loans_month_count' => $totalLoansCountThisMonth,
            'total_active_loans' => $totalActiveLoans,
            'total_active_loans_value' => $totalActiveLoansValue,

            // Repayments
            'repayments_due' => $repaymentsDue,
            'repayments_due_count' => $repaymentsDueCount,
            'repayments_due_today' => $repaymentsDueToday,
            'repayments_due_today_count' => $repaymentsDueTodayCount,

            // Savings
            'savings_count' => $totalSavingsCount,
            'savings_value' => $totalSavingsValue,
            'savings_month' => $savingsThisMonth,

            // Investments
            'investors_count' => $totalInvestors,
            'investment_value' => $totalInvestmentValue,
            'investment_month' => $investmentThisMonth,

            // Disbursements
            'disbursements_month' => $disbursementsThisMonth,
            'disbursements_month_count' => $disbursementsThisMonthCount,

            // Pending Actions
            'pending_signature' => $pendingSignature,
            'pending_approval' => $pendingApproval,
            'pending_disbursement' => $pendingDisbursement,
        ];

        // === GET CHART DATA FOR LOANS vs SAVINGS (Last 6 months) ===
        $chartData = $this->getChartData();

        // === GET RECENT ACTIVITY (Latest 10 records) ===
        $recentActivity = $this->getRecentActivity();

        return view('admin.home', compact('stats', 'chartData', 'recentActivity'));
    }

    /**
     * Get chart data for Loans vs Savings (last 6 months)
     */
    private function getChartData()
    {
        $months = [];
        $loansData = [];
        $savingsData = [];
        
        // Detect schema
        $loansInfo = $this->getLoansTableInfo();
        $groupLoansTimestamp = $this->getTimestampColumn('group_loans');
        $savingsTimestamp = $this->getTimestampColumn('savings');

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;
            $monthName = $date->format('M Y');

            $months[] = $monthName;

            // Loans disbursed this month (personal + group)
            $personalLoans = DB::table($loansInfo['table'])
                ->whereMonth($loansInfo['timestamp'], $month)
                ->whereYear($loansInfo['timestamp'], $year)
                ->where('verified', '1')
                ->sum('principal');

            $groupLoans = DB::table('group_loans')
                ->whereMonth($groupLoansTimestamp, $month)
                ->whereYear($groupLoansTimestamp, $year)
                ->where('verified', '1')
                ->sum('principal');

            $loansData[] = $personalLoans + $groupLoans;

            // Savings this month
            $savings = DB::table('savings')
                ->whereMonth($savingsTimestamp, $month)
                ->whereYear($savingsTimestamp, $year)
                ->sum('value');

            $savingsData[] = $savings;
        }

        return [
            'months' => $months,
            'loans' => $loansData,
            'savings' => $savingsData,
        ];
    }

    /**
     * Get recent activity (loans, disbursements, repayments)
     */
    private function getRecentActivity()
    {
        $activities = [];
        
        // Detect schema
        $loansInfo = $this->getLoansTableInfo();

        // Recent loan applications
        $recentLoans = DB::table($loansInfo['table'] . ' as pl')
            ->join('members as m', 'pl.member_id', '=', 'm.id')
            ->select(
                'pl.id',
                'pl.' . $loansInfo['timestamp'] . ' as created_at',
                DB::raw("CONCAT(m.fname, ' ', m.lname) as member_name"),
                'pl.principal as amount',
                'pl.status'
            )
            ->orderBy('pl.' . $loansInfo['timestamp'], 'desc')
            ->limit(5)
            ->get();

        foreach ($recentLoans as $loan) {
            $statusText = match($loan->status) {
                '0' => 'Pending Signature',
                '1' => 'Active',
                '2' => 'Pending Approval',
                '3' => 'Pending Disbursement',
                '4' => 'Disbursed',
                '5' => 'Closed',
                default => 'Unknown'
            };

            $statusBadge = match($loan->status) {
                '0' => 'warning',
                '1' => 'success',
                '2' => 'info',
                '3' => 'primary',
                '4' => 'success',
                '5' => 'secondary',
                default => 'dark'
            };

            $activities[] = (object)[
                'created_at' => Carbon::parse($loan->created_at),
                'description' => "Loan application by {$loan->member_name} (UGX " . number_format($loan->amount) . ")",
                'status' => $statusText,
                'status_badge' => $statusBadge,
            ];
        }

        // Sort by date
        usort($activities, function($a, $b) {
            return $b->created_at <=> $a->created_at;
        });

        return array_slice($activities, 0, 10);
    }

    public function editUser($user)
    {
        // You can load the user model and pass to the view if needed
        return view('admin.user-management.users.edit', compact('user'));
    }
}
