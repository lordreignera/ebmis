<?php
/**
 * Fix Unpaid Schedule Issues for Old Loans
 * 
 * This script helps diagnose and fix loans that show as outstanding
 * even though payments were made in the old system.
 * 
 * Usage: php fix_unpaid_schedules.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FIX UNPAID SCHEDULES FOR OLD LOANS ===\n\n";

// Find loans by member name or loan code
echo "Enter search term (member name or loan code): ";
$handle = fopen("php://stdin", "r");
$searchTerm = trim(fgets($handle));
fclose($handle);

if (empty($searchTerm)) {
    echo "❌ Search term required\n";
    exit;
}

// Search for loans
$loans = DB::table('personal_loans as pl')
    ->join('members as m', 'pl.member_id', '=', 'm.id')
    ->where(function($query) use ($searchTerm) {
        $query->where('pl.code', 'LIKE', "%{$searchTerm}%")
              ->orWhere('m.fname', 'LIKE', "%{$searchTerm}%")
              ->orWhere('m.lname', 'LIKE', "%{$searchTerm}%")
              ->orWhere(DB::raw("CONCAT(m.fname, ' ', m.lname)"), 'LIKE', "%{$searchTerm}%");
    })
    ->select(
        'pl.id',
        'pl.code',
        'pl.principal',
        'pl.paid',
        'pl.status',
        'm.fname',
        'm.lname',
        'm.contact'
    )
    ->get();

if ($loans->isEmpty()) {
    echo "❌ No loans found matching '{$searchTerm}'\n";
    exit;
}

echo "\n=== LOANS FOUND ===\n";
foreach ($loans as $index => $loan) {
    $statusText = match($loan->status) {
        0 => 'Pending',
        1 => 'Approved',
        2 => 'Disbursed',
        3 => 'Closed',
        4 => 'Rejected',
        default => 'Unknown'
    };
    
    $balance = $loan->principal - $loan->paid;
    
    echo "\n[" . ($index + 1) . "] Loan: {$loan->code}\n";
    echo "    Member: {$loan->fname} {$loan->lname}\n";
    echo "    Principal: UGX " . number_format($loan->principal) . "\n";
    echo "    Paid: UGX " . number_format($loan->paid) . "\n";
    echo "    Balance: UGX " . number_format($balance) . "\n";
    echo "    Status: {$statusText}\n";
}

echo "\nSelect loan number to analyze (or 0 to exit): ";
$handle = fopen("php://stdin", "r");
$selection = (int)trim(fgets($handle));
fclose($handle);

if ($selection < 1 || $selection > count($loans)) {
    echo "Exiting...\n";
    exit;
}

$selectedLoan = $loans[$selection - 1];

echo "\n=== ANALYZING LOAN: {$selectedLoan->code} ===\n";
echo "Member: {$selectedLoan->fname} {$selectedLoan->lname}\n\n";

// Get loan schedules
$schedules = DB::table('loan_schedules')
    ->where('loan_id', $selectedLoan->id)
    ->orderBy('id')
    ->get();

echo "SCHEDULES:\n";
echo str_pad("ID", 6) . str_pad("Payment Date", 15) . str_pad("Due", 15) . str_pad("Paid", 15) . str_pad("Balance", 15) . "Status\n";
echo str_repeat("-", 80) . "\n";

$totalDue = 0;
$totalPaid = 0;
$unpaidSchedules = [];

foreach ($schedules as $schedule) {
    $statusText = match($schedule->status) {
        0 => 'UNPAID',
        1 => 'PAID',
        2 => 'OVERDUE',
        3 => 'PARTIAL',
        default => 'UNKNOWN'
    };
    
    $balance = $schedule->payment - $schedule->paid;
    $totalDue += $schedule->payment;
    $totalPaid += $schedule->paid;
    
    if ($schedule->status != 1 && $balance > 0) {
        $unpaidSchedules[] = $schedule;
    }
    
    echo str_pad($schedule->id, 6) . 
         str_pad(date('Y-m-d', strtotime($schedule->payment_date)), 15) . 
         str_pad(number_format($schedule->payment), 15) . 
         str_pad(number_format($schedule->paid), 15) . 
         str_pad(number_format($balance), 15) . 
         $statusText . "\n";
}

echo str_repeat("-", 80) . "\n";
echo str_pad("TOTAL:", 21) . str_pad(number_format($totalDue), 15) . str_pad(number_format($totalPaid), 15) . str_pad(number_format($totalDue - $totalPaid), 15) . "\n\n";

// Get actual repayments
$repayments = DB::table('repayments')
    ->where('loan_id', $selectedLoan->id)
    ->orderBy('date_created')
    ->get();

echo "REPAYMENTS RECORDED:\n";
echo str_pad("ID", 8) . str_pad("Date", 20) . str_pad("Amount", 15) . str_pad("Type", 15) . str_pad("Status", 10) . "Pay Status\n";
echo str_repeat("-", 80) . "\n";

$totalRepayments = 0;
foreach ($repayments as $repayment) {
    $typeText = match($repayment->type) {
        1 => 'Cash',
        2 => 'Mobile Money',
        3 => 'Bank Transfer',
        default => 'Unknown'
    };
    
    $statusText = $repayment->status == 1 ? 'Confirmed' : 'Pending';
    $totalRepayments += $repayment->amount;
    
    echo str_pad($repayment->id, 8) . 
         str_pad(date('Y-m-d H:i', strtotime($repayment->date_created)), 20) . 
         str_pad(number_format($repayment->amount), 15) . 
         str_pad($typeText, 15) . 
         str_pad($statusText, 10) . 
         ($repayment->pay_status ?? 'N/A') . "\n";
}

echo str_repeat("-", 80) . "\n";
echo "Total Repayments: UGX " . number_format($totalRepayments) . "\n\n";

// Analysis
echo "=== ANALYSIS ===\n";
$discrepancy = $totalRepayments - $totalPaid;

if ($discrepancy > 0) {
    echo "⚠️  DISCREPANCY FOUND!\n";
    echo "    Total repayments recorded: UGX " . number_format($totalRepayments) . "\n";
    echo "    Total marked as paid in schedules: UGX " . number_format($totalPaid) . "\n";
    echo "    Difference: UGX " . number_format($discrepancy) . "\n\n";
    echo "This suggests payments were recorded but schedules weren't updated.\n\n";
}

if (count($unpaidSchedules) > 0) {
    echo "❌ Found " . count($unpaidSchedules) . " unpaid schedule(s) with outstanding balance\n\n";
    
    echo "OPTIONS:\n";
    echo "[1] Mark ALL schedules as PAID (if loan is actually paid off)\n";
    echo "[2] Mark SPECIFIC schedules as PAID\n";
    echo "[3] Redistribute payments across schedules\n";
    echo "[0] Exit without changes\n\n";
    
    echo "Select option: ";
    $handle = fopen("php://stdin", "r");
    $option = (int)trim(fgets($handle));
    fclose($handle);
    
    switch ($option) {
        case 1:
            // Mark all as paid
            echo "\n⚠️  This will mark ALL schedules as PAID and close the loan.\n";
            echo "Type 'CONFIRM' to proceed: ";
            $handle = fopen("php://stdin", "r");
            $confirm = trim(fgets($handle));
            fclose($handle);
            
            if ($confirm === 'CONFIRM') {
                DB::beginTransaction();
                try {
                    // Update all schedules
                    DB::table('loan_schedules')
                        ->where('loan_id', $selectedLoan->id)
                        ->update([
                            'status' => 1,
                            'paid' => DB::raw('payment'),
                            'date_cleared' => now()
                        ]);
                    
                    // Update loan status
                    DB::table('personal_loans')
                        ->where('id', $selectedLoan->id)
                        ->update([
                            'status' => 3, // Closed
                            'paid' => $selectedLoan->principal
                        ]);
                    
                    DB::commit();
                    echo "\n✅ All schedules marked as PAID and loan closed!\n";
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    echo "\n❌ Error: " . $e->getMessage() . "\n";
                }
            } else {
                echo "Cancelled.\n";
            }
            break;
            
        case 2:
            // Mark specific schedules
            echo "\nEnter schedule IDs to mark as paid (comma-separated): ";
            $handle = fopen("php://stdin", "r");
            $ids = trim(fgets($handle));
            fclose($handle);
            
            $scheduleIds = array_map('trim', explode(',', $ids));
            
            DB::beginTransaction();
            try {
                foreach ($scheduleIds as $scheduleId) {
                    DB::table('loan_schedules')
                        ->where('id', $scheduleId)
                        ->where('loan_id', $selectedLoan->id)
                        ->update([
                            'status' => 1,
                            'paid' => DB::raw('payment'),
                            'date_cleared' => now()
                        ]);
                }
                
                // Recalculate loan paid amount
                $totalPaidNow = DB::table('loan_schedules')
                    ->where('loan_id', $selectedLoan->id)
                    ->sum('paid');
                
                DB::table('personal_loans')
                    ->where('id', $selectedLoan->id)
                    ->update(['paid' => $totalPaidNow]);
                
                DB::commit();
                echo "\n✅ Selected schedules marked as PAID!\n";
                
            } catch (\Exception $e) {
                DB::rollBack();
                echo "\n❌ Error: " . $e->getMessage() . "\n";
            }
            break;
            
        case 3:
            // Redistribute payments
            echo "\n⚠️  This will redistribute all recorded payments across schedules in order.\n";
            echo "Type 'CONFIRM' to proceed: ";
            $handle = fopen("php://stdin", "r");
            $confirm = trim(fgets($handle));
            fclose($handle);
            
            if ($confirm === 'CONFIRM') {
                DB::beginTransaction();
                try {
                    $remainingAmount = $totalRepayments;
                    
                    foreach ($schedules as $schedule) {
                        if ($remainingAmount <= 0) break;
                        
                        $payment = min($remainingAmount, $schedule->payment);
                        $status = $payment >= $schedule->payment ? 1 : 3; // 1=Paid, 3=Partial
                        
                        DB::table('loan_schedules')
                            ->where('id', $schedule->id)
                            ->update([
                                'paid' => $payment,
                                'status' => $status,
                                'date_cleared' => $status == 1 ? now() : null
                            ]);
                        
                        $remainingAmount -= $payment;
                    }
                    
                    DB::table('personal_loans')
                        ->where('id', $selectedLoan->id)
                        ->update(['paid' => $totalRepayments]);
                    
                    DB::commit();
                    echo "\n✅ Payments redistributed across schedules!\n";
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    echo "\n❌ Error: " . $e->getMessage() . "\n";
                }
            } else {
                echo "Cancelled.\n";
            }
            break;
            
        default:
            echo "Exiting without changes...\n";
    }
} else {
    echo "✅ All schedules are properly marked as PAID!\n";
    
    if ($selectedLoan->status != 3 && $totalPaid >= $selectedLoan->principal) {
        echo "\n💡 Loan appears fully paid but status is not 'Closed'.\n";
        echo "Would you like to close this loan? (yes/no): ";
        $handle = fopen("php://stdin", "r");
        $response = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($response) === 'yes') {
            DB::table('personal_loans')
                ->where('id', $selectedLoan->id)
                ->update(['status' => 3, 'date_closed' => now()]);
            
            echo "✅ Loan status updated to CLOSED!\n";
        }
    }
}

echo "\nDone!\n";
