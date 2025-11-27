<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LateFee;
use App\Models\PersonalLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LateFeeController extends Controller
{
    /**
     * Display a listing of late fees
     */
    public function index(Request $request)
    {
        $query = LateFee::with(['loan.member', 'schedule', 'member'])
            ->orderBy('calculated_date', 'desc');
        
        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('from_date') && $request->from_date) {
            $query->where('schedule_due_date', '>=', $request->from_date);
        }
        
        if ($request->has('to_date') && $request->to_date) {
            $query->where('schedule_due_date', '<=', $request->to_date);
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
        
        // Get summary statistics
        $stats = [
            'total' => LateFee::sum('amount'),
            'pending' => LateFee::pending()->sum('amount'),
            'paid' => LateFee::paid()->sum('amount'),
            'waived' => LateFee::waived()->sum('amount'),
            'count' => LateFee::count(),
            'pending_count' => LateFee::pending()->count(),
        ];
        
        return view('admin.late-fees.index', compact('lateFees', 'stats'));
    }
    
    /**
     * Waive a single late fee
     */
    public function waive(Request $request, LateFee $lateFee)
    {
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
        return view('admin.late-fees.waive-upgrade');
    }
    
    /**
     * Process waiver for upgrade period
     */
    public function waiveUpgradePeriod(Request $request)
    {
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
