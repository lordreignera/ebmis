<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\DashboardEvent;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;

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
        $today = Carbon::today()->format('Y-m-d'); // Format as string for comparison
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

        // Active loans (status = 2 = Disbursed OR status = 3 with unpaid schedules)
        // Get all potential active loans first
        $potentialActivePersonalLoans = PersonalLoan::whereIn('status', [2, 3])
            ->with('schedules')
            ->get();
        
        // Filter to only truly active loans using getActualStatus()
        $activePersonalLoansFiltered = $potentialActivePersonalLoans->filter(function($loan) {
            return $loan->getActualStatus() === 'running';
        });
        
        $activePersonalLoans = $activePersonalLoansFiltered->count();

        // Outstanding amount - sum of principal for active loans
        $activePersonalLoansValue = $activePersonalLoansFiltered->sum('principal');

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

        // Active group loans (status = 2 = Disbursed OR status = 3 with unpaid schedules)
        // Get all potential active group loans first
        $potentialActiveGroupLoans = GroupLoan::whereIn('status', [2, 3])
            ->with('schedules')
            ->get();
        
        // Filter to only truly active loans using getActualStatus()
        $activeGroupLoansFiltered = $potentialActiveGroupLoans->filter(function($loan) {
            return $loan->getActualStatus() === 'running';
        });
        
        $activeGroupLoans = $activeGroupLoansFiltered->count();

        // Outstanding amount for group loans - sum of principal for active loans
        $activeGroupLoansValue = $activeGroupLoansFiltered->sum('principal');

        // === TOTAL LOANS (Personal + Group) ===
        $totalLoansCount = $personalLoansCount + $groupLoansCount;
        $totalLoansValue = $personalLoansValue + $groupLoansValue;
        $totalLoansThisMonth = $personalLoansThisMonth + $groupLoansThisMonth;
        $totalLoansCountThisMonth = $personalLoansCountThisMonth + $groupLoansCountThisMonth;
        $totalActiveLoans = $activePersonalLoans + $activeGroupLoans;
        $totalActiveLoansValue = $activePersonalLoansValue + $activeGroupLoansValue;

        // === REPAYMENTS DUE (Overdue) ===
        // Count unique LOANS (not schedules) that have overdue payments
        // payment_date is stored as DD-MM-YYYY format, need to convert for comparison
        $repaymentsDue = DB::table('loan_schedules')
            ->where('status', '0')
            ->whereRaw("STR_TO_DATE(payment_date, '%d-%m-%Y') < ?", [$today])
            ->sum('payment');

        // Count unique loans with overdue schedules
        $repaymentsDueCount = DB::table('loan_schedules')
            ->where('status', '0')
            ->whereRaw("STR_TO_DATE(payment_date, '%d-%m-%Y') < ?", [$today])
            ->distinct('loan_id')
            ->count('loan_id');

        // === REPAYMENTS DUE TODAY ===
        // payment_date is stored as DD-MM-YYYY format, need to convert for comparison
        $todayFormatted = Carbon::parse($today)->format('d-m-Y');
        $repaymentsDueToday = DB::table('loan_schedules')
            ->where('status', '0')
            ->where('payment_date', $todayFormatted)
            ->sum('payment');

        $repaymentsDueTodayCount = DB::table('loan_schedules')
            ->where('status', '0')
            ->where('payment_date', $todayFormatted)
            ->count();

        // === CASH SECURITIES ===
        // Use cash_securities table (new system) + savings table (legacy/fallback)
        // Cash Securities are loan collateral deposits from members
        $cashSecuritiesCount = DB::table('cash_securities')
            ->where('status', 1) // Only paid/confirmed securities
            ->count();

        $cashSecuritiesValue = DB::table('cash_securities')
            ->where('status', 1)
            ->sum('amount') ?? 0;

        $cashSecuritiesThisMonth = DB::table('cash_securities')
            ->where('status', 1)
            ->whereMonth('datecreated', $currentMonth)
            ->whereYear('datecreated', $currentYear)
            ->sum('amount') ?? 0;

        // Legacy savings table (for backwards compatibility)
        $legacySavingsCount = DB::table('savings')->count();
        $legacySavingsValue = DB::table('savings')->sum('value') ?? 0;
        
        $legacySavingsThisMonth = DB::table('savings')
            ->whereMonth($savingsTimestamp, $currentMonth)
            ->whereYear($savingsTimestamp, $currentYear)
            ->sum('value') ?? 0;

        // TOTAL = New cash_securities + Legacy savings
        $totalCashSecuritiesCount = $cashSecuritiesCount + $legacySavingsCount;
        $totalCashSecuritiesValue = $cashSecuritiesValue + $legacySavingsValue;
        $totalCashSecuritiesThisMonth = $cashSecuritiesThisMonth + $legacySavingsThisMonth;

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
        // Status: 0=Pending (needs approval), 1=Approved (ready for disbursement), 2=Disbursed
        $pendingSignature = 0; // Not tracked in current schema
        
        // Pending Approval: status=0 (awaiting approval)
        $pendingApproval = DB::table($loansInfo['table'])
            ->where('status', '0')
            ->count();

        // Pending Disbursement: status=1 AND not yet disbursed
        // Check personal_loans that are approved (status=1) but haven't been disbursed yet
        $pendingDisbursement = PersonalLoan::where('status', 1)
            ->whereDoesntHave('disbursements', function($q) {
                $q->where('status', 2); // No successful disbursement
            })
            ->count();
        
        // Add group loans that are approved but not disbursed
        $pendingDisbursement += GroupLoan::where('status', 1)
            ->whereDoesntHave('disbursements', function($q) {
                $q->where('status', 2);
            })
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

            // Cash Securities (Savings)
            'savings_count' => $totalCashSecuritiesCount,
            'savings_value' => $totalCashSecuritiesValue,
            'savings_month' => $totalCashSecuritiesThisMonth,

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

        // === GET DASHBOARD CALENDAR PREVIEW ===
        $calendarPreview = $this->getCalendarPreview();

        // === GET RECENT ACTIVITY (Latest 10 records) ===
        $recentActivity = $this->getRecentActivity();

        return view('admin.home', compact('stats', 'chartData', 'calendarPreview', 'recentActivity'));
    }

    public function storeDashboardEvent(Request $request)
    {
        abort_unless(
            $request->user()?->isSuperAdmin()
                || $request->user()?->isAdministrator()
                || $request->user()?->can('manage-dashboard-events'),
            403
        );

        if (!Schema::hasTable('dashboard_events')) {
            return back()->with('error', 'Dashboard events are not ready. Please run the latest migrations.');
        }

        $validated = $request->validateWithBag('dashboardEvent', [
            'title' => ['required', 'string', 'max:120'],
            'event_date' => ['required', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'category' => ['required', 'in:general,collection,field_visit,meeting,approval'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DashboardEvent::create([
            'user_id' => $request->user()?->id,
            'title' => $validated['title'],
            'event_date' => $validated['event_date'],
            'event_time' => $validated['event_time'] ?? null,
            'category' => $validated['category'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Dashboard event added successfully.');
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

    private function getCalendarPreview(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $calendarStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $upcomingEnd = $today->copy()->addDays(14);

        $calendarEvents = $this->getManualDashboardEvents($calendarStart, $calendarEnd)
            ->concat($this->getRepaymentCalendarEvents($calendarStart, $calendarEnd));

        $eventsByDate = $calendarEvents->groupBy('date');
        $days = [];

        for ($date = $calendarStart->copy(); $date->lte($calendarEnd); $date->addDay()) {
            $dateKey = $date->toDateString();
            $dayEvents = $eventsByDate->get($dateKey, collect());

            $days[] = [
                'date' => $dateKey,
                'day' => $date->format('j'),
                'is_today' => $date->isSameDay($today),
                'in_month' => $date->isSameMonth($today),
                'events_count' => $dayEvents->count(),
                'has_collection' => $dayEvents->contains('category', 'collection'),
                'has_manual' => $dayEvents->contains('source', 'manual'),
            ];
        }

        $upcomingEvents = $this->getManualDashboardEvents($today, $upcomingEnd)
            ->concat($this->getRepaymentCalendarEvents($today, $upcomingEnd))
            ->sortBy([
                ['date', 'asc'],
                ['time_sort', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->take(6);

        return [
            'events_enabled' => Schema::hasTable('dashboard_events'),
            'month_label' => $today->format('F Y'),
            'weekdays' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'days' => $days,
            'upcoming' => $upcomingEvents,
        ];
    }

    private function getManualDashboardEvents(Carbon $startDate, Carbon $endDate)
    {
        if (!Schema::hasTable('dashboard_events')) {
            return collect();
        }

        return DashboardEvent::query()
            ->whereBetween('event_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('event_date')
            ->orderByRaw('event_time IS NULL, event_time')
            ->get()
            ->map(function (DashboardEvent $event) {
                $eventDate = Carbon::parse($event->event_date);

                return [
                    'source' => 'manual',
                    'category' => $event->category,
                    'badge' => $this->calendarCategoryLabel($event->category),
                    'title' => $event->title,
                    'subtitle' => $event->notes,
                    'date' => $eventDate->toDateString(),
                    'date_label' => $eventDate->format('D, M j'),
                    'time_label' => $event->event_time ? Carbon::parse($event->event_time)->format('g:i A') : 'All day',
                    'time_sort' => $event->event_time ?: '00:00:00',
                    'url' => null,
                ];
            });
    }

    private function getRepaymentCalendarEvents(Carbon $startDate, Carbon $endDate)
    {
        $events = collect();
        $dateValues = $this->scheduleDateLookupValues($startDate, $endDate);

        if (empty($dateValues)) {
            return $events;
        }

        if (Schema::hasTable('loan_schedules') && Schema::hasTable('personal_loans') && Schema::hasTable('members')) {
            $personalSchedules = DB::table('loan_schedules as ls')
                ->join('personal_loans as pl', 'pl.id', '=', 'ls.loan_id')
                ->leftJoin('members as m', 'm.id', '=', 'pl.member_id')
                ->where('ls.status', '0')
                ->whereIn('ls.payment_date', $dateValues)
                ->select(
                    'ls.id',
                    'ls.loan_id',
                    'ls.payment_date',
                    'ls.payment',
                    DB::raw("TRIM(CONCAT(COALESCE(m.fname, ''), ' ', COALESCE(m.lname, ''))) as borrower_name")
                )
                ->limit(120)
                ->get();

            $events = $events->concat($personalSchedules->map(function ($schedule) {
                return $this->mapRepaymentScheduleEvent($schedule, 'Personal loan');
            }));
        }

        if (Schema::hasTable('group_loan_schedules') && Schema::hasTable('group_loans') && Schema::hasTable('groups')) {
            $groupSchedules = DB::table('group_loan_schedules as gls')
                ->join('group_loans as gl', 'gl.id', '=', 'gls.loan_id')
                ->leftJoin('groups as g', 'g.id', '=', 'gl.group_id')
                ->where('gls.status', '0')
                ->whereIn('gls.payment_date', $dateValues)
                ->select(
                    'gls.id',
                    'gls.loan_id',
                    'gls.payment_date',
                    'gls.payment',
                    DB::raw("COALESCE(g.name, 'Group loan') as borrower_name")
                )
                ->limit(80)
                ->get();

            $events = $events->concat($groupSchedules->map(function ($schedule) {
                return $this->mapRepaymentScheduleEvent($schedule, 'Group loan');
            }));
        }

        return $events
            ->filter()
            ->sortBy([
                ['date', 'asc'],
                ['title', 'asc'],
            ])
            ->values();
    }

    private function mapRepaymentScheduleEvent($schedule, string $loanType): ?array
    {
        $dueDate = $this->parseScheduleDate($schedule->payment_date);

        if (!$dueDate) {
            return null;
        }

        $borrowerName = trim($schedule->borrower_name ?: $loanType);

        return [
            'source' => 'repayment',
            'category' => 'collection',
            'badge' => 'Collection',
            'title' => $borrowerName,
            'subtitle' => $loanType . ' repayment - UGX ' . number_format((float) $schedule->payment),
            'date' => $dueDate->toDateString(),
            'date_label' => $dueDate->format('D, M j'),
            'time_label' => 'Repayment',
            'time_sort' => '23:59:59',
            'url' => route('admin.loans.repayments.schedules', $schedule->loan_id),
        ];
    }

    private function scheduleDateLookupValues(Carbon $startDate, Carbon $endDate): array
    {
        $values = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $values[] = $date->format('d-m-Y');
            $values[] = $date->format('Y-m-d');
        }

        return array_values(array_unique($values));
    }

    private function parseScheduleDate(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        foreach (['d-m-Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->startOfDay();
            } catch (\Throwable $e) {
                // Try the next supported legacy format.
            }
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function calendarCategoryLabel(string $category): string
    {
        return match ($category) {
            'collection' => 'Collection',
            'field_visit' => 'Field visit',
            'meeting' => 'Meeting',
            'approval' => 'Approval',
            default => 'General',
        };
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
            // Correct status mapping: 0=Pending, 1=Approved for Disbursement, 2=Disbursed, 3=Completed
            $statusText = match($loan->status) {
                '0' => 'Pending Approval',
                '1' => 'Approved for Disbursement',
                '2' => 'Disbursed',
                '3' => 'Completed',
                default => 'Unknown'
            };

            $statusBadge = match($loan->status) {
                '0' => 'warning',
                '1' => 'primary',
                '2' => 'success',
                '3' => 'secondary',
                default => 'dark'
            };

            $activities[] = (object)[
                'created_at' => Carbon::parse($loan->created_at),
                'description' => "Loan application by {$loan->member_name} (UGX " . number_format($loan->amount) . ") - {$statusText}",
                'status' => $loan->status, // Add raw status for routing
                'status_text' => $statusText,
                'status_badge' => $statusBadge,
                'loan_id' => $loan->id,
            ];
        }

        // Sort by date
        usort($activities, function($a, $b) {
            return $b->created_at <=> $a->created_at;
        });

        return array_slice($activities, 0, 10);
    }

}
