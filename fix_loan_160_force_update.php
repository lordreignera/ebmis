<?php

/**
 * FORCE UPDATE Loan 160 Schedule - Fix Wrong Dates
 * 
 * The current schedules have completely wrong dates (April, July, October, etc.)
 * This script will FORCE update them to the correct weekly schedule:
 * - Disbursement: January 8, 2026 (Thursday)
 * - First Payment: January 15, 2026 (Thursday) [disbursement + 7 days]
 * - Subsequent: Every Thursday (7 days apart)
 * 
 * Usage: php fix_loan_160_force_update.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "===========================================\n";
echo "FORCE FIX Loan 160 Schedule - Correct Dates\n";
echo "===========================================\n\n";

try {
    // Get loan details
    $loan = DB::table('personal_loans')->where('id', 160)->first();
    
    if (!$loan) {
        echo "❌ Loan 160 not found!\n";
        exit(1);
    }
    
    echo "✓ Loan: {$loan->code}\n";
    echo "  Member ID: {$loan->member_id}\n";
    echo "  Principal: UGX " . number_format($loan->principal, 0) . "\n";
    echo "  Period: {$loan->period} installments\n\n";
    
    // Get disbursement
    $disbursement = DB::table('disbursements')
        ->where('loan_id', 160)
        ->where('status', 2)
        ->first();
    
    if (!$disbursement) {
        echo "❌ No disbursement found!\n";
        exit(1);
    }
    
    $disbursementDate = Carbon::parse($disbursement->created_at);
    echo "✓ Disbursement Date: {$disbursementDate->format('l, F j, Y \a\t g:i A')}\n\n";
    
    // Calculate correct first payment date: Disbursement + 7 days
    $firstPaymentDate = $disbursementDate->copy()->addDays(7);
    
    echo "✓ Correct First Payment: {$firstPaymentDate->format('l, F j, Y')}\n";
    echo "  (Every {$firstPaymentDate->format('l')} thereafter)\n\n";
    
    // Get current schedules BY ID (creation order, not payment_date!)
    $schedules = DB::table('loan_schedules')
        ->where('loan_id', 160)
        ->orderBy('id')  // <-- ORDER BY ID, NOT payment_date!
        ->get();
    
    echo "✓ Found {$schedules->count()} payment schedules\n\n";
    
    // Show WRONG dates (first 8)
    echo "CURRENT WRONG DATES (by ID order):\n";
    echo "-----------------------------------\n";
    foreach ($schedules->take(8) as $index => $schedule) {
        $current = Carbon::parse($schedule->payment_date);
        echo "  Schedule #{$schedule->id}: {$current->format('l, M j, Y')} ❌\n";
    }
    echo "\n";
    
    // Show correct dates
    echo "CORRECT DATES (should be):\n";
    echo "-------------------------\n";
    $currentDate = $firstPaymentDate->copy();
    for ($i = 0; $i < min(8, $schedules->count()); $i++) {
        echo "  Payment #" . ($i + 1) . ": {$currentDate->format('l, M j, Y')} ✓\n";
        $currentDate->addWeek();
    }
    echo "\n";
    
    // Ask for confirmation
    echo "⚠️  This will UPDATE all {$schedules->count()} payment dates!\n";
    echo "Do you want to proceed? (yes/no): ";
    
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'yes') {
        echo "\n❌ Update cancelled.\n";
        exit(0);
    }
    
    // Start transaction
    DB::beginTransaction();
    
    echo "\nUpdating payment schedules...\n\n";
    
    $currentPaymentDate = $firstPaymentDate->copy();
    $updatedCount = 0;
    
    foreach ($schedules as $index => $schedule) {
        $scheduleNumber = $index + 1;
        $oldDate = Carbon::parse($schedule->payment_date);
        $newDate = $currentPaymentDate->copy();
        
        // Update the schedule
        DB::table('loan_schedules')
            ->where('id', $schedule->id)
            ->update([
                'payment_date' => $newDate->format('Y-m-d')
            ]);
        
        if ($scheduleNumber <= 10 || $scheduleNumber > ($schedules->count() - 3)) {
            echo "  Schedule #{$schedule->id}: {$oldDate->format('M j, Y')} → {$newDate->format('l, M j, Y')}\n";
        } elseif ($scheduleNumber == 11) {
            echo "  ... (schedules 11-" . ($schedules->count() - 3) . " updated) ...\n";
        }
        
        $updatedCount++;
        $currentPaymentDate->addWeek();
    }
    
    DB::commit();
    
    echo "\n✅ Successfully updated {$updatedCount} payment schedules!\n\n";
    echo "New Schedule Summary:\n";
    echo "--------------------\n";
    echo "  - First Payment: {$firstPaymentDate->format('l, F j, Y')}\n";
    echo "  - Payment Day: Every {$firstPaymentDate->format('l')}\n";
    echo "  - Last Payment: {$currentPaymentDate->subWeek()->format('l, F j, Y')}\n";
    echo "\n✓ Loan 160 schedule is now CORRECTED!\n\n";
    
    // Verify the fix
    echo "Verification - First 5 payments:\n";
    echo "--------------------------------\n";
    $verifySchedules = DB::table('loan_schedules')
        ->where('loan_id', 160)
        ->orderBy('id')
        ->take(5)
        ->get(['id', 'payment_date']);
    
    foreach ($verifySchedules as $index => $vs) {
        $date = Carbon::parse($vs->payment_date);
        echo "  #" . ($index + 1) . ": {$date->format('l, M j, Y')} ✓\n";
    }
    
} catch (\Exception $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
