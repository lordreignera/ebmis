<?php

/**
 * Reschedule Loan 160 based on New Payment Rules
 * 
 * This script reschedules a loan that was disbursed on 8/1/2026 (yesterday)
 * to align with the new payment calculation rules.
 * 
 * NEW RULE (from LoanScheduleHelper):
 * - Weekly loans: First payment 7 days after disbursement, then every 7 days
 * - If the loan was disbursed on 8/1/2026, first payment should be on 15/1/2026 (7 days later)
 * 
 * Usage: php reschedule_loan_160.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\LoanScheduleHelper;

echo "===========================================\n";
echo "Reschedule Loan 160 - New Payment Rules\n";
echo "===========================================\n\n";

try {
    // Step 1: Get loan details
    echo "Step 1: Fetching loan details...\n";
    $loan = DB::table('personal_loans')->where('id', 160)->first();
    
    if (!$loan) {
        echo "❌ Loan 160 not found!\n";
        exit(1);
    }
    
    echo "✓ Loan found: {$loan->code}\n";
    echo "  - Member ID: {$loan->member_id}\n";
    echo "  - Principal: UGX " . number_format($loan->principal, 0) . "\n";
    echo "  - Period: {$loan->period} installments\n";
    
    // Step 2: Get disbursement date
    $disbursement = DB::table('disbursements')
        ->where('loan_id', 160)
        ->where('status', 2) // Status 2 = completed
        ->orderBy('created_at', 'desc')
        ->first();
    
    if (!$disbursement) {
        echo "❌ No completed disbursement found for loan 160!\n";
        exit(1);
    }
    
    $disbursementDate = Carbon::parse($disbursement->created_at);
    echo "\n✓ Disbursement date: {$disbursementDate->format('d/m/Y (l)')}\n";
    
    // Step 3: Get product details to determine period type
    $product = DB::table('products')->where('id', $loan->product_type)->first();
    $periodType = $product ? $product->period_type : 1; // Default to weekly
    
    $periodTypeName = [1 => 'Weekly', 2 => 'Monthly', 3 => 'Daily'][$periodType] ?? 'Unknown';
    echo "  - Period Type: {$periodTypeName} (type {$periodType})\n";
    
    // Step 4: Calculate expected first payment date using NEW RULES
    $expectedFirstPayment = LoanScheduleHelper::calculatePaymentDate($disbursementDate, 1, $periodType);
    echo "\n✓ Expected first payment date (NEW RULE): {$expectedFirstPayment->format('d/m/Y (l)')}\n";
    
    // Step 5: Get current schedules
    $schedules = DB::table('loan_schedules')
        ->where('loan_id', 160)
        ->orderBy('payment_date')
        ->get();
    
    if ($schedules->isEmpty()) {
        echo "\n❌ No schedules found for loan 160!\n";
        exit(1);
    }
    
    echo "\n✓ Found {$schedules->count()} schedules\n";
    
    // Check current first payment date
    $currentFirstPayment = Carbon::parse($schedules->first()->payment_date);
    echo "  - Current first payment: {$currentFirstPayment->format('d/m/Y (l)')}\n";
    
    if ($currentFirstPayment->eq($expectedFirstPayment)) {
        echo "\n✅ Schedules are already aligned with new rules. No changes needed.\n";
        exit(0);
    }
    
    echo "\n⚠️  Schedule misalignment detected!\n";
    echo "  - Current first payment: {$currentFirstPayment->format('d/m/Y')}\n";
    echo "  - Should be: {$expectedFirstPayment->format('d/m/Y')}\n";
    
    // Step 6: Ask for confirmation
    echo "\nDo you want to reschedule all {$schedules->count()} payment dates? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'yes') {
        echo "\n❌ Rescheduling cancelled.\n";
        exit(0);
    }
    
    // Step 7: Start transaction and reschedule
    DB::beginTransaction();
    
    echo "\nRescheduling payments...\n";
    $updatedCount = 0;
    
    foreach ($schedules as $index => $schedule) {
        $scheduleNumber = $index + 1;
        
        // Calculate new payment date using the helper
        $newPaymentDate = LoanScheduleHelper::calculatePaymentDate($disbursementDate, $scheduleNumber, $periodType);
        $oldPaymentDate = Carbon::parse($schedule->payment_date);
        
        // Update the schedule
        DB::table('loan_schedules')
            ->where('id', $schedule->id)
            ->update([
                'payment_date' => $newPaymentDate->format('Y-m-d')
            ]);
        
        echo "  Schedule #{$scheduleNumber}: {$oldPaymentDate->format('d/m/Y')} → {$newPaymentDate->format('d/m/Y')}\n";
        $updatedCount++;
    }
    
    DB::commit();
    
    echo "\n✅ Successfully rescheduled {$updatedCount} payments for loan 160!\n";
    echo "\nNew payment schedule:\n";
    echo "  - First payment: {$expectedFirstPayment->format('d/m/Y (l)')}\n";
    
    if ($periodType == 1) {
        echo "  - Subsequent payments: Every Friday\n";
    } elseif ($periodType == 2) {
        echo "  - Subsequent payments: Monthly on same day\n";
    } elseif ($periodType == 3) {
        echo "  - Subsequent payments: Daily (skip Sundays)\n";
    }
    
    echo "\n✓ Loan 160 is now aligned with new payment rules!\n";
    
} catch (\Exception $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
