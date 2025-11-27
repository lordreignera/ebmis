<?php

/**
 * Calculate and Record All Current Late Fees
 * 
 * This script calculates late fees for all overdue schedules and stores them
 * in the late_fees table for proper tracking and management.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\LateFee;

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "   CALCULATE AND RECORD LATE FEES\n";
echo "   For All Overdue Loan Schedules\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "\n";

$today = Carbon::now();
echo "Processing Date: " . $today->format('d-m-Y H:i:s') . "\n\n";

echo "─────────────────────────────────────────────────────────────────────\n";
echo "STEP 1: Finding all overdue schedules...\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

$overdueSchedules = DB::table('loan_schedules')
    ->where('status', 0) // Unpaid
    ->get()
    ->filter(function($schedule) use ($today) {
        try {
            $dueDate = Carbon::createFromFormat('d-m-Y', $schedule->payment_date);
            return $dueDate->isPast();
        } catch (\Exception $e) {
            return false;
        }
    });

echo "Total overdue schedules: " . $overdueSchedules->count() . "\n\n";

if ($overdueSchedules->count() == 0) {
    echo "✓ No overdue schedules found.\n\n";
    exit(0);
}

echo "─────────────────────────────────────────────────────────────────────\n";
echo "STEP 2: Calculating late fees...\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

$loanIds = $overdueSchedules->pluck('loan_id')->unique();

$loans = DB::table('personal_loans as pl')
    ->join('products as p', 'pl.product_type', '=', 'p.id')
    ->whereIn('pl.id', $loanIds)
    ->select('pl.*', 'p.period_type', 'p.name as product_name')
    ->get()
    ->keyBy('id');

$totalLateFees = 0;
$recordsToInsert = [];
$processedCount = 0;
$skippedCount = 0;

foreach ($overdueSchedules as $schedule) {
    $loan = $loans->get($schedule->loan_id);
    if (!$loan) {
        $skippedCount++;
        continue;
    }
    
    try {
        // Check if late fee already exists for this schedule
        $existing = DB::table('late_fees')
            ->where('schedule_id', $schedule->id)
            ->where('calculated_date', $today->format('Y-m-d'))
            ->exists();
            
        if ($existing) {
            $skippedCount++;
            continue;
        }
        
        // EXACT RepaymentController calculation
        $now = time();
        $your_date = strtotime($schedule->payment_date);
        $datediff = $now - $your_date;
        $d = floor($datediff / (60 * 60 * 24)); // Days overdue
        
        $dd = 0; // Periods overdue
        $periodType = '';
        
        if ($d > 0) {
            if ($loan->period_type == '1') {
                $dd = ceil($d / 7);
                $periodType = 'Weekly';
            } else if ($loan->period_type == '2') {
                $dd = ceil($d / 30);
                $periodType = 'Monthly';
            } else if ($loan->period_type == '3') {
                $dd = $d;
                $periodType = 'Daily';
            } else {
                $dd = ceil($d / 7);
                $periodType = 'Weekly';
            }
        }
        
        $daysOverdue = $d;
        $periodsOverdue = $dd;
        
        // Calculate late fee using EXACT formula from RepaymentController
        $intrestamtpayable = $schedule->interest;
        $lateFee = (($schedule->principal + $intrestamtpayable) * 0.06) * $dd;
        $scheduleAmount = $schedule->principal + $intrestamtpayable;
        
        if ($lateFee > 0) {
            $totalLateFees += $lateFee;
            
            $calculationDetails = [
                'schedule_amount' => $scheduleAmount,
                'principal' => $schedule->principal,
                'interest' => $schedule->interest,
                'rate' => 0.06,
                'rate_percent' => '6%',
                'formula' => '(principal + interest) × 6% × periods_overdue'
            ];
            
            $recordsToInsert[] = [
                'loan_id' => $loan->id,
                'schedule_id' => $schedule->id,
                'member_id' => $loan->member_id,
                'amount' => $lateFee,
                'days_overdue' => $daysOverdue,
                'periods_overdue' => $periodsOverdue,
                'period_type' => $periodType,
                'schedule_due_date' => $dueDate->format('Y-m-d'),
                'calculated_date' => $today->format('Y-m-d'),
                'calculation_details' => json_encode($calculationDetails),
                'status' => 0, // Pending
                'created_at' => $today,
                'updated_at' => $today
            ];
            
            $processedCount++;
            
            if ($processedCount % 100 == 0) {
                echo "  ... processed {$processedCount} schedules\n";
            }
        }
        
    } catch (\Exception $e) {
        $skippedCount++;
        continue;
    }
}

echo "  Schedules processed: {$processedCount}\n";
echo "  Schedules skipped: {$skippedCount}\n";
echo "  Total late fees: " . number_format($totalLateFees, 0) . " UGX\n\n";

if (empty($recordsToInsert)) {
    echo "✓ No new late fees to record.\n\n";
    exit(0);
}

echo "─────────────────────────────────────────────────────────────────────\n";
echo "SUMMARY:\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

echo "Records to insert: " . count($recordsToInsert) . "\n";
echo "Total amount: " . number_format($totalLateFees, 0) . " UGX\n\n";

// Show sample records
echo "Sample late fees (first 5):\n\n";
foreach (array_slice($recordsToInsert, 0, 5) as $idx => $record) {
    $member = DB::table('members')->find($record['member_id']);
    $memberName = $member ? trim("{$member->fname} {$member->lname}") : 'Unknown';
    $loan = $loans->get($record['loan_id']);
    
    echo ($idx + 1) . ". {$memberName}\n";
    echo "   Loan: " . ($loan->code ?? 'N/A') . "\n";
    echo "   Due Date: " . Carbon::parse($record['schedule_due_date'])->format('d-m-Y') . "\n";
    echo "   Days Overdue: {$record['days_overdue']}\n";
    echo "   Late Fee: " . number_format($record['amount'], 0) . " UGX\n\n";
}

if (count($recordsToInsert) > 5) {
    echo "... and " . (count($recordsToInsert) - 5) . " more\n\n";
}

echo "─────────────────────────────────────────────────────────────────────\n";
echo "Do you want to save these late fees to the database? (yes/no): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n✗ Operation cancelled.\n\n";
    exit(0);
}

echo "\n";
echo "─────────────────────────────────────────────────────────────────────\n";
echo "STEP 3: Saving late fees to database...\n";
echo "─────────────────────────────────────────────────────────────────────\n\n";

DB::beginTransaction();

try {
    // Insert in chunks for better performance
    $chunks = array_chunk($recordsToInsert, 100);
    $insertedCount = 0;
    
    foreach ($chunks as $chunk) {
        DB::table('late_fees')->insert($chunk);
        $insertedCount += count($chunk);
        echo "  ... inserted {$insertedCount} records\n";
    }
    
    DB::commit();
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "   ✓ SUCCESS\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    echo "Late fees recorded:\n";
    echo "  Total records: {$insertedCount}\n";
    echo "  Total amount: " . number_format($totalLateFees, 0) . " UGX\n";
    echo "  Date: " . $today->format('Y-m-d H:i:s') . "\n\n";
    
    echo "Next steps:\n";
    echo "  1. Review recorded late fees in late_fees table\n";
    echo "  2. Run waiver script to waive upgrade period fees\n";
    echo "  3. Monitor late fees going forward\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════════\n";
    echo "   ✗ ERROR\n";
    echo "═══════════════════════════════════════════════════════════════════\n\n";
    
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    
    echo "Transaction rolled back - no changes made.\n\n";
    exit(1);
}
