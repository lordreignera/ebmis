<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupLoan;
use App\Models\LateFee;
use App\Models\PersonalLoan;
use App\Services\LoanAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LateFeeController extends Controller
{
    public function __construct(private LoanAccessService $loanAccessService) {}

    private function requireSuperAdmin()
    {
        if (!auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only the Super Administrator can waive late fees.');
        }
    }

    /**
     * Display a listing of late fees
     */
    public function index(Request $request)
    {
        $filters = [
            'from_date' => $request->input('from_date', now()->startOfMonth()->toDateString()),
            'to_date' => $request->input('to_date', now()->endOfMonth()->toDateString()),
        ];

        $query = LateFee::with(['loan.member', 'schedule', 'member'])
            ->orderBy('calculated_date', 'desc');
        
        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($filters['from_date']) {
            $query->where('schedule_due_date', '>=', $filters['from_date']);
        }
        
        if ($filters['to_date']) {
            $query->where('schedule_due_date', '<=', $filters['to_date']);
        }
        
        // Search by member name or loan code
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('member', function($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                  ->orWhere('lname', 'like', "%{$search}%");
            })->orWhereHas('loan', function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%");
            });
        }
        
        $lateFees = $query->paginate(50);
        
        $stats = $this->activeLoanLateFeeStats($filters['from_date'], $filters['to_date']);
        
        return view('admin.late-fees.index', compact('lateFees', 'stats', 'filters'));
    }

    private function activeLoanLateFeeStats(?string $fromDate = null, ?string $toDate = null): array
    {
        $fromTimestamp = $fromDate ? strtotime($fromDate . ' 00:00:00') : null;
        $toTimestamp = $toDate ? strtotime($toDate . ' 23:59:59') : null;

        $personalLoans = $this->loanAccessService
            ->scopeActiveLoanQuery(
                PersonalLoan::with([
                    'product',
                    'schedules' => fn ($query) => $query->where('status', '!=', 1),
                ])->whereIn('status', [2, 3])
            )
            ->get()
            ->filter(fn ($loan) => $loan->getActualStatus() === 'running')
            ->values();

        $groupLoans = $this->loanAccessService
            ->scopeActiveLoanQuery(
                GroupLoan::with([
                    'product',
                    'schedules' => fn ($query) => $query->where('status', '!=', 1),
                ])->whereIn('status', [2, 3])
            )
            ->get()
            ->filter(fn ($loan) => $loan->getActualStatus() === 'running')
            ->values();

        $personalScheduleIds = $personalLoans->flatMap(fn ($loan) => $loan->schedules)->pluck('id')->all();
        $groupScheduleIds = $groupLoans->flatMap(fn ($loan) => $loan->schedules)->pluck('id')->all();

        $personalPaidBySchedule = $this->confirmedSchedulePayments($personalScheduleIds, 'repayments');
        $groupPaidBySchedule = $this->confirmedSchedulePayments($groupScheduleIds, 'group_repayments');
        $personalPaidBeforeRange = $this->confirmedSchedulePayments($personalScheduleIds, 'repayments', null, $fromDate);
        $groupPaidBeforeRange = $this->confirmedSchedulePayments($groupScheduleIds, 'group_repayments', null, $fromDate);
        $rangeEndExclusive = $toDate ? date('Y-m-d', strtotime($toDate . ' +1 day')) : null;
        $personalPaidToRangeEnd = $this->confirmedSchedulePayments($personalScheduleIds, 'repayments', null, $rangeEndExclusive);
        $groupPaidToRangeEnd = $this->confirmedSchedulePayments($groupScheduleIds, 'group_repayments', null, $rangeEndExclusive);

        $loanScheduleKeys = $personalLoans
            ->flatMap(fn ($loan) => $loan->schedules->map(fn ($schedule) => $this->lateFeeKey($loan->id, $schedule->id)))
            ->merge($groupLoans->flatMap(fn ($loan) => $loan->schedules->map(fn ($schedule) => $this->lateFeeKey($loan->id, $schedule->id))))
            ->unique()
            ->values()
            ->all();

        $lateFeeRows = empty($loanScheduleKeys)
            ? collect()
            : DB::table('late_fees')
                ->select('loan_id', 'schedule_id', 'status', 'amount', 'paid_at', 'waived_at', 'created_at', 'updated_at')
                ->where(function ($query) use ($personalLoans, $groupLoans) {
                    $loanIds = $personalLoans->pluck('id')->merge($groupLoans->pluck('id'))->unique()->all();
                    $query->whereIn('loan_id', $loanIds);
                })
                ->get();

        $waivedBySchedule = [];
        $waivedInRangeBySchedule = [];
        $paidLateFeeRowsBySchedule = [];
        $paidLateFeeRowsInRangeBySchedule = [];

        foreach ($lateFeeRows as $row) {
            $key = $this->lateFeeKey($row->loan_id, $row->schedule_id);
            if (!in_array($key, $loanScheduleKeys, true)) {
                continue;
            }

            if ((int) $row->status === LateFee::STATUS_WAIVED) {
                $waivedBySchedule[$key] = ($waivedBySchedule[$key] ?? 0) + (float) $row->amount;

                if ($this->timestampInRange(strtotime((string) ($row->waived_at ?? $row->updated_at ?? $row->created_at)), $fromTimestamp, $toTimestamp)) {
                    $waivedInRangeBySchedule[$key] = ($waivedInRangeBySchedule[$key] ?? 0) + (float) $row->amount;
                }
            } elseif ((int) $row->status === LateFee::STATUS_PAID) {
                $paidLateFeeRowsBySchedule[$key] = ($paidLateFeeRowsBySchedule[$key] ?? 0) + (float) $row->amount;

                if ($this->timestampInRange(strtotime((string) ($row->paid_at ?? $row->updated_at ?? $row->created_at)), $fromTimestamp, $toTimestamp)) {
                    $paidLateFeeRowsInRangeBySchedule[$key] = ($paidLateFeeRowsInRangeBySchedule[$key] ?? 0) + (float) $row->amount;
                }
            }
        }

        $stats = [
            'gross' => 0.0,
            'outstanding' => 0.0,
            'total' => 0.0,
            'pending' => 0.0,
            'paid' => 0.0,
            'waived' => 0.0,
            'count' => 0,
            'pending_count' => 0,
            'record_count' => LateFee::count(),
        ];

        foreach ($personalLoans as $loan) {
            $this->addLoanLateFeeStats($stats, $loan, $personalPaidBySchedule, $personalPaidBeforeRange, $personalPaidToRangeEnd, $waivedBySchedule, $waivedInRangeBySchedule, $paidLateFeeRowsBySchedule, $paidLateFeeRowsInRangeBySchedule, $fromTimestamp, $toTimestamp);
        }

        foreach ($groupLoans as $loan) {
            $this->addLoanLateFeeStats($stats, $loan, $groupPaidBySchedule, $groupPaidBeforeRange, $groupPaidToRangeEnd, $waivedBySchedule, $waivedInRangeBySchedule, $paidLateFeeRowsBySchedule, $paidLateFeeRowsInRangeBySchedule, $fromTimestamp, $toTimestamp);
        }

        return $stats;
    }

    private function addLoanLateFeeStats(
        array &$stats,
        $loan,
        array $paidBySchedule,
        array $paidBeforeRange,
        array $paidToRangeEnd,
        array $waivedBySchedule,
        array $waivedInRangeBySchedule,
        array $paidLateFeeRowsBySchedule,
        array $paidLateFeeRowsInRangeBySchedule,
        ?int $fromTimestamp,
        ?int $toTimestamp
    ): void
    {
        foreach ($loan->schedules as $schedule) {
            $dueTimestamp = $this->parsePaymentDate($schedule->payment_date);
            if (!$this->timestampInRange($dueTimestamp, $fromTimestamp, $toTimestamp)) {
                continue;
            }

            $gross = $this->calculateScheduleGrossLateFee($schedule, $loan);
            if ($gross <= 0.01) {
                continue;
            }

            $key = $this->lateFeeKey($loan->id, $schedule->id);
            $waived = (float) ($waivedBySchedule[$key] ?? 0);
            $paid = (float) ($paidBySchedule[$schedule->id] ?? 0);
            $paidBefore = (float) ($paidBeforeRange[$schedule->id] ?? 0);
            $paidToEnd = (float) ($paidToRangeEnd[$schedule->id] ?? $paid);
            $principalInterest = (float) $schedule->principal + (float) $schedule->interest;
            $lateFeesPaidFromRepayments = max(0, $paid - $principalInterest);
            $lateFeesPaidFromRepaymentsInRange = max(0, $paidToEnd - $principalInterest) - max(0, $paidBefore - $principalInterest);
            $lateFeesPaid = max($lateFeesPaidFromRepayments, (float) ($paidLateFeeRowsBySchedule[$key] ?? 0));
            $lateFeesPaidInRange = max($lateFeesPaidFromRepaymentsInRange, (float) ($paidLateFeeRowsInRangeBySchedule[$key] ?? 0));
            $pending = max(0, $gross - $waived - $lateFeesPaid);

            $stats['gross'] += $gross;
            $stats['waived'] += (float) ($waivedInRangeBySchedule[$key] ?? 0);
            $stats['paid'] += $lateFeesPaidInRange;
            $stats['outstanding'] += $pending;
            $stats['total'] = $stats['outstanding'];
            $stats['pending'] = $stats['outstanding'];
            $stats['count']++;

            if ($pending > 0.01) {
                $stats['pending_count']++;
            }
        }
    }

    private function calculateScheduleGrossLateFee($schedule, $loan): float
    {
        $freezeDate = $schedule->date_cleared ?? null;
        $asOfTimestamp = $freezeDate ? strtotime((string) $freezeDate) : time();
        $dueTimestamp = $this->parsePaymentDate($schedule->payment_date);

        if (!$dueTimestamp) {
            return 0.0;
        }

        $daysOverdue = max(0, (int) floor(($asOfTimestamp - $dueTimestamp) / 86400));
        if ($daysOverdue <= 0) {
            return 0.0;
        }

        $periodType = (string) optional($loan->product)->period_type;
        $periodsOverdue = match ($periodType) {
            '1' => (int) ceil($daysOverdue / 7),
            '2' => (int) ceil($daysOverdue / 30),
            default => $daysOverdue,
        };

        return ((float) $schedule->principal + (float) $schedule->interest) * 0.06 * $periodsOverdue;
    }

    private function confirmedSchedulePayments(array $scheduleIds, string $table, ?string $fromDate = null, ?string $toDate = null): array
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

            if ($fromDate) {
                $query->where('date_created', '>=', $fromDate);
            }

            if ($toDate) {
                $query->where('date_created', '<', $toDate);
            }
        } else {
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }

            if ($toDate) {
                $query->where('created_at', '<', $toDate);
            }
        }

        return $query
            ->groupBy('schedule_id')
            ->select('schedule_id', DB::raw('SUM(amount) as total_paid'))
            ->pluck('total_paid', 'schedule_id')
            ->map(fn ($amount) => (float) $amount)
            ->toArray();
    }

    private function parsePaymentDate($date): int|false
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->getTimestamp();
        }

        $date = (string) $date;
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $matches)) {
            return mktime(0, 0, 0, (int) $matches[2], (int) $matches[1], (int) $matches[3]);
        }

        return strtotime($date);
    }

    private function lateFeeKey($loanId, $scheduleId): string
    {
        return (int) $loanId . ':' . (int) $scheduleId;
    }

    private function timestampInRange(int|false|null $timestamp, ?int $fromTimestamp, ?int $toTimestamp): bool
    {
        if (!$timestamp) {
            return false;
        }

        if ($fromTimestamp && $timestamp < $fromTimestamp) {
            return false;
        }

        if ($toTimestamp && $timestamp > $toTimestamp) {
            return false;
        }

        return true;
    }
    
    /**
     * Waive a single late fee
     */
    public function waive(Request $request, LateFee $lateFee)
    {
        $this->requireSuperAdmin();

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);
        
        if ($lateFee->status != 0) {
            return back()->with('error', 'Only pending late fees can be waived.');
        }
        
        $lateFee->waive($request->reason, auth()->id());
        
        return back()->with('success', 'Late fee waived successfully.');
    }
    
    /**
     * Bulk waive late fees for a date range
     */
    public function bulkWaive(Request $request)
    {
        $this->requireSuperAdmin();

        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|max:500'
        ]);
        
        $count = LateFee::pending()
            ->whereBetween('schedule_due_date', [$request->from_date, $request->to_date])
            ->count();
        
        if ($count == 0) {
            return back()->with('info', 'No pending late fees found for the selected date range.');
        }
        
        LateFee::pending()
            ->whereBetween('schedule_due_date', [$request->from_date, $request->to_date])
            ->update([
                'status' => 2, // Waived
                'waiver_reason' => $request->reason,
                'waived_at' => now(),
                'waived_by' => auth()->id()
            ]);
        
        return back()->with('success', "Successfully waived {$count} late fees.");
    }
    
    /**
     * Show waive upgrade period form
     */
    public function showWaiveUpgrade()
    {
        $this->requireSuperAdmin();

        return view('admin.late-fees.waive-upgrade');
    }
    
    /**
     * Process waiver for upgrade period
     */
    public function waiveUpgradePeriod(Request $request)
    {
        $this->requireSuperAdmin();

        $request->validate([
            'confirm' => 'required|accepted'
        ]);
        
        $upgradeStart = \Carbon\Carbon::parse('2025-10-30');
        $upgradeEnd = \Carbon\Carbon::parse('2025-11-27');
        
        // Run the proportional waiver logic
        $overdueSchedules = DB::table('loan_schedules as ls')
            ->join('personal_loans as pl', 'ls.loan_id', '=', 'pl.id')
            ->join('members as m', 'pl.member_id', '=', 'm.id')
            ->leftJoin('late_fees as lf', function($join) {
                $join->on('ls.id', '=', 'lf.schedule_id')
                     ->where('lf.status', '=', 0);
            })
            ->where('ls.status', 0)
            ->where('ls.payment_date', '<', $upgradeEnd->format('Y-m-d'))
            ->select(
                'ls.id as schedule_id',
                'ls.loan_id',
                'ls.payment_date as due_date',
                'ls.principal',
                'ls.interest',
                'pl.period as period_type',
                'm.id as member_id',
                'lf.id as late_fee_id'
            )
            ->get();
        
        $waiversToApply = [];
        $totalWaiverAmount = 0;
        
        foreach ($overdueSchedules as $schedule) {
            $dueDate = \Carbon\Carbon::parse($schedule->due_date);
            $periodType = $schedule->period_type ?? '2';
            
            $totalDaysOverdue = $dueDate->diffInDays($upgradeEnd);
            
            if ($totalDaysOverdue <= 0) continue;
            
            $daysInUpgrade = 0;
            if ($dueDate->lessThan($upgradeStart)) {
                $daysInUpgrade = $upgradeStart->diffInDays($upgradeEnd) + 1;
            } elseif ($dueDate->between($upgradeStart, $upgradeEnd)) {
                $daysInUpgrade = $dueDate->diffInDays($upgradeEnd) + 1;
            }
            
            if ($daysInUpgrade <= 0) continue;
            
            $scheduleAmount = $schedule->principal + $schedule->interest;
            $upgradePeriodsOverdue = 0;
            
            if ($periodType == '1') {
                $upgradePeriodsOverdue = ceil($daysInUpgrade / 7);
            } elseif ($periodType == '2') {
                $upgradePeriodsOverdue = ceil($daysInUpgrade / 30);
            } elseif ($periodType == '3') {
                $upgradePeriodsOverdue = $daysInUpgrade;
            }
            
            $waiverAmount = ($scheduleAmount * 0.06) * $upgradePeriodsOverdue;
            
            if ($waiverAmount > 0) {
                $waiversToApply[] = [
                    'schedule_id' => $schedule->schedule_id,
                    'loan_id' => $schedule->loan_id,
                    'member_id' => $schedule->member_id,
                    'waiver_amount' => $waiverAmount,
                    'days_in_upgrade' => $daysInUpgrade,
                    'upgrade_periods' => $upgradePeriodsOverdue,
                    'period_type' => $periodType,
                    'due_date' => $schedule->due_date,
                    'late_fee_id' => $schedule->late_fee_id
                ];
                
                $totalWaiverAmount += $waiverAmount;
            }
        }
        
        if (count($waiversToApply) == 0) {
            return back()->with('info', 'No late fees found to waive for the upgrade period.');
        }
        
        DB::beginTransaction();
        try {
            $created = 0;
            $updated = 0;
            $waiver_reason = 'Late fees accumulated during system upgrade period (Oct 30 - Nov 27, 2025)';
            
            foreach ($waiversToApply as $waiver) {
                if ($waiver['late_fee_id']) {
                    DB::table('late_fees')
                        ->where('id', $waiver['late_fee_id'])
                        ->update([
                            'status' => 2,
                            'waiver_reason' => $waiver_reason,
                            'waived_at' => now(),
                            'waived_by' => auth()->id(),
                            'updated_at' => now()
                        ]);
                    $updated++;
                } else {
                    DB::table('late_fees')->insert([
                        'loan_id' => $waiver['loan_id'],
                        'schedule_id' => $waiver['schedule_id'],
                        'member_id' => $waiver['member_id'],
                        'amount' => $waiver['waiver_amount'],
                        'days_overdue' => $waiver['days_in_upgrade'],
                        'periods_overdue' => $waiver['upgrade_periods'],
                        'period_type' => $waiver['period_type'],
                        'schedule_due_date' => \Carbon\Carbon::parse($waiver['due_date'])->format('Y-m-d'),
                        'calculated_date' => now(),
                        'status' => 2,
                        'waiver_reason' => $waiver_reason,
                        'waived_at' => now(),
                        'waived_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $created++;
                }
            }
            
            DB::commit();
            
            return back()->with('success', "Successfully waived " . number_format($totalWaiverAmount, 0) . " UGX in late fees ({$created} created, {$updated} updated).");
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing waivers: ' . $e->getMessage());
        }
    }
}
