<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;
use App\Models\LoanSchedule;
use App\Models\LateFee;
use App\Models\Product;
use Carbon\Carbon;

echo "=================================================\n";
echo "WAIVE NOVEMBER 2025 LATE FEES FOR ALL DAILY LOANS\n";
echo "=================================================\n\n";

echo "Policy: Waive late fees that accrued DURING November 2025\n";
echo "because of system upgrade (clients not responsible)\n\n";

// Define November period
$novemberStart = Carbon::create(2025, 11, 1, 0, 0, 0);
$novemberEnd = Carbon::create(2025, 11, 30, 23, 59, 59);
$today = Carbon::now();

echo "November 2025: {$novemberStart->format('Y-m-d')} to {$novemberEnd->format('Y-m-d')}\n\n";

// Get all daily loan products
$dailyProducts = Product::where('period_type', 3)->get();
$productIds = $dailyProducts->pluck('id')->toArray();

// Get all daily loans
$dailyLoans = PersonalLoan::whereIn('product_type', $productIds)
    ->where('status', 2) // Disbursed
    ->with('member')
    ->get();

echo "Processing {$dailyLoans->count()} daily loans...\n\n";

$totalWaived = 0;
$totalWaivedAmount = 0;
$loansAffected = 0;

foreach ($dailyLoans as $loan) {
    // Get ALL overdue schedules (paid or unpaid)
    $schedules = LoanSchedule::where('loan_id', $loan->id)
        ->where('payment_date', '<', $today)
        ->orderBy('payment_date')
        ->get();
    
    if ($schedules->isEmpty()) {
        continue;
    }
    
    $loanWaived = 0;
    $loanWaivedAmount = 0;
    
    foreach ($schedules as $schedule) {
        $dueDate = Carbon::parse($schedule->payment_date);
        
        // Calculate how many days of the overdue period fall in November
        $novemberDays = 0;
        
        // If schedule was due before November started
        if ($dueDate->lt($novemberStart)) {
            // Was this schedule paid?
            if ($schedule->status == 1 && $schedule->date_cleared) {
                $paidDate = Carbon::parse($schedule->date_cleared);
                
                // If paid after November ended, count all 30 days of November
                if ($paidDate->gt($novemberEnd)) {
                    $novemberDays = 30;
                }
                // If paid during November, count days from Nov 1 to payment date
                else if ($paidDate->gte($novemberStart) && $paidDate->lte($novemberEnd)) {
                    $novemberDays = $novemberStart->diffInDays($paidDate);
                }
                // If paid before November, no November days
                else {
                    $novemberDays = 0;
                }
            } else {
                // Still unpaid - count all 30 days of November
                $novemberDays = 30;
            }
        }
        // If schedule was due during November
        else if ($dueDate->gte($novemberStart) && $dueDate->lte($novemberEnd)) {
            // Was it paid?
            if ($schedule->status == 1 && $schedule->date_cleared) {
                $paidDate = Carbon::parse($schedule->date_cleared);
                
                // If paid after November, count from due date to Nov 30
                if ($paidDate->gt($novemberEnd)) {
                    $novemberDays = $dueDate->diffInDays($novemberEnd->addDay());
                }
                // If paid during November, count from due date to payment date
                else if ($paidDate->gte($novemberStart) && $paidDate->lte($novemberEnd)) {
                    $novemberDays = $dueDate->diffInDays($paidDate);
                }
            } else {
                // Still unpaid - count from due date to Nov 30
                $novemberDays = $dueDate->diffInDays($novemberEnd->addDay());
            }
        }
        // If due after November, no waiver
        else {
            $novemberDays = 0;
        }
        
        if ($novemberDays > 0) {
            $scheduleDue = $schedule->principal + $schedule->interest;
            $novemberLateFee = ($scheduleDue * 0.06) * $novemberDays;
            
            // Check if already waived
            $existingWaiver = LateFee::where('schedule_id', $schedule->id)
                ->where('status', 2)
                ->first();
            
            if (!$existingWaiver) {
                // Create waiver record
                LateFee::create([
                    'loan_id' => $loan->id,
                    'schedule_id' => $schedule->id,
                    'member_id' => $loan->member_id,
                    'amount' => $novemberLateFee,
                    'days_overdue' => $novemberDays,
                    'periods_overdue' => $novemberDays, // For daily loans, periods = days
                    'period_type' => 'Daily',
                    'schedule_due_date' => $schedule->payment_date,
                    'calculated_date' => now()->format('Y-m-d'),
                    'status' => 2, // Waived
                    'waiver_reason' => 'System upgrade in November 2025 - clients unable to make payments',
                    'waived_at' => now(),
                    'waived_by' => 1
                ]);
                
                $loanWaived++;
                $loanWaivedAmount += $novemberLateFee;
            }
        }
    }
    
    if ($loanWaived > 0) {
        echo "Loan {$loan->id} - {$loan->code} ({$loan->member->fname} {$loan->member->lname}):\n";
        echo "  Waived: {$loanWaived} schedules (November portion)\n";
        echo "  Amount: UGX " . number_format($loanWaivedAmount, 2) . "\n\n";
        
        $loansAffected++;
        $totalWaived += $loanWaived;
        $totalWaivedAmount += $loanWaivedAmount;
    }
}

echo "=================================================\n";
echo "SUMMARY\n";
echo "=================================================\n";
echo "Loans Affected: {$loansAffected}\n";
echo "Schedules with November Waivers: {$totalWaived}\n";
echo "Total Waived (November only): UGX " . number_format($totalWaivedAmount, 2) . "\n\n";

echo "✓ November late fees waived (system upgrade period)\n";
echo "✓ October late fees will still be charged (client responsibility)\n";
echo "✓ December late fees will be charged (system operational)\n";
