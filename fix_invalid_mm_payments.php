<?php
/**
 * Fix Invalid Mobile Money Payments
 * 
 * Identifies mobile money payments marked as confirmed (status=1) but with:
 * - NULL txn_id
 * - Empty txn_id
 * - Invalid txn_id (not starting with 'EbP')
 * 
 * These payments were initiated but never actually confirmed by FlexiPay,
 * yet the system marked them as paid.
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     FIX INVALID MOBILE MONEY PAYMENTS                          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Find invalid payments
$invalidPayments = DB::table('repayments as r')
    ->join('loan_schedules as ls', 'r.schedule_id', '=', 'ls.id')
    ->join('personal_loans as pl', 'r.loan_id', '=', 'pl.id')
    ->join('members as m', 'pl.member_id', '=', 'm.id')
    ->select('r.id', 'r.loan_id', 'r.amount', 'r.txn_id', 'r.date_created', 
             'm.fname', 'm.lname', 'ls.id as schedule_id', 'ls.paid', 'ls.principal', 'ls.interest')
    ->where('r.type', 2) // Mobile money
    ->where('r.status', 1) // Confirmed
    ->where(function($query) {
        $query->whereNull('r.txn_id')
              ->orWhere('r.txn_id', '')
              ->orWhereRaw("r.txn_id NOT LIKE 'EbP%'");
    })
    ->orderBy('r.date_created', 'desc')
    ->get();

echo "Found " . count($invalidPayments) . " invalid mobile money payments\n\n";

if (count($invalidPayments) > 0) {
    $totalAmount = 0;
    $summary = [];
    
    foreach ($invalidPayments as $p) {
        $key = "{$p->fname} {$p->lname}";
        if (!isset($summary[$key])) {
            $summary[$key] = [
                'count' => 0,
                'amount' => 0,
                'loans' => []
            ];
        }
        $summary[$key]['count']++;
        $summary[$key]['amount'] += $p->amount;
        $summary[$key]['loans'][$p->loan_id] = true;
        $totalAmount += $p->amount;
    }
    
    echo "SUMMARY BY MEMBER:\n";
    echo str_repeat("─", 80) . "\n";
    foreach ($summary as $member => $data) {
        $loanCount = count($data['loans']);
        echo sprintf("  %-30s | %2d payments | UGX %12.0f | %d loan(s)\n", 
            $member, $data['count'], $data['amount'], $loanCount);
    }
    echo str_repeat("─", 80) . "\n";
    printf("  %-30s | %2d payments | UGX %12.0f\n\n", 
        'TOTAL', count($invalidPayments), $totalAmount);
    
    echo "DETAILED LIST:\n";
    echo str_repeat("─", 120) . "\n";
    printf("%-6s | %-18s | %-12s | %-15s | %-20s | %-12s\n", 
        "ID", "Member", "Amount", "Loan ID", "TXN ID", "Date");
    echo str_repeat("─", 120) . "\n";
    
    foreach ($invalidPayments as $p) {
        printf("%-6d | %-18s | UGX %9.0f | %-15d | %-20s | %s\n",
            $p->id,
            substr("{$p->fname} {$p->lname}", 0, 18),
            $p->amount,
            $p->loan_id,
            $p->txn_id ?: 'NULL',
            date('Y-m-d H:i', strtotime($p->date_created))
        );
    }
    echo str_repeat("─", 120) . "\n\n";
    
    // Confirmation
    echo "ACTION TO BE TAKEN:\n";
    echo "  1. Mark these repayments as INVALID (status = -1)\n";
    echo "  2. Reverse the paid amounts on schedules\n";
    echo "  3. Unmark schedules as paid if needed\n";
    echo "  4. Reopen loans if not all schedules are paid\n\n";
    
    echo "Total Amount to be Reversed: UGX " . number_format($totalAmount, 0) . "\n\n";
    
    echo "Do you want to proceed? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $response = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($response) === 'yes') {
        DB::beginTransaction();
        
        try {
            $fixedCount = 0;
            $affectedSchedules = [];
            $affectedLoans = [];
            
            foreach ($invalidPayments as $p) {
                // Mark repayment as invalid
                DB::table('repayments')
                    ->where('id', $p->id)
                    ->update([
                        'status' => -1,
                        'pay_status' => 'INVALID',
                        'pay_message' => 'Invalid mobile money - no FlexiPay confirmation'
                    ]);
                
                $affectedSchedules[] = $p->schedule_id;
                $affectedLoans[] = $p->loan_id;
                
                // Reduce schedule paid amount
                $newPaid = max(0, $p->paid - $p->amount);
                DB::table('loan_schedules')
                    ->where('id', $p->schedule_id)
                    ->update(['paid' => $newPaid]);
                
                // If schedule was marked as paid, unmark it
                $totalDue = $p->principal + $p->interest;
                if ($newPaid < $totalDue) {
                    DB::table('loan_schedules')
                        ->where('id', $p->schedule_id)
                        ->update(['status' => 0]);
                }
                
                $fixedCount++;
                echo "✓ Fixed payment ID {$p->id} (Member: {$p->fname} {$p->lname}, Amount: " . 
                     number_format($p->amount, 0) . ")\n";
            }
            
            echo "\nRe-checking affected loan statuses...\n";
            $uniqueLoans = array_unique($affectedLoans);
            
            foreach ($uniqueLoans as $loanId) {
                $totalSchedules = DB::table('loan_schedules')
                    ->where('loan_id', $loanId)
                    ->count();
                
                $paidSchedules = DB::table('loan_schedules')
                    ->where('loan_id', $loanId)
                    ->where('status', 1)
                    ->count();
                
                if ($paidSchedules < $totalSchedules) {
                    // Reopen the loan
                    $updated = DB::table('personal_loans')
                        ->where('id', $loanId)
                        ->update(['status' => 2]);
                    
                    if ($updated) {
                        $loanCode = DB::table('personal_loans')->where('id', $loanId)->value('code');
                        echo "✓ Reopened loan {$loanId} ({$loanCode}) - {$paidSchedules}/{$totalSchedules} schedules paid\n";
                    }
                }
            }
            
            DB::commit();
            
            echo "\n";
            echo "╔════════════════════════════════════════════════════════════════╗\n";
            echo "║     ✓ ALL INVALID PAYMENTS HAVE BEEN FIXED                     ║\n";
            echo "╚════════════════════════════════════════════════════════════════╝\n";
            echo "\nSummary:\n";
            echo "  - Repayments Fixed: $fixedCount\n";
            echo "  - Loans Reopened: " . count(array_unique($affectedLoans)) . "\n";
            echo "  - Amount Reversed: UGX " . number_format($totalAmount, 0) . "\n\n";
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "\n✗ ERROR: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
        }
    } else {
        echo "\nOperation cancelled.\n\n";
    }
} else {
    echo "✓ No invalid mobile money payments found!\n\n";
}

echo "Done.\n\n";
?>
