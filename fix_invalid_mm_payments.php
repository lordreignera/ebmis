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
 * 
 * Creates audit trail of all reversions for compliance and tracking.
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     FIX INVALID MOBILE MONEY PAYMENTS WITH AUDIT TRAIL         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Create audit table if it doesn't exist
DB::statement("
    CREATE TABLE IF NOT EXISTS payment_reversion_audit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        repayment_id INT NOT NULL,
        loan_id INT NOT NULL,
        member_id INT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        payment_date DATETIME NOT NULL,
        initiated_by_user_id INT NOT NULL,
        initiated_by_name VARCHAR(100),
        txn_id VARCHAR(100),
        reversion_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        reversion_reason VARCHAR(255),
        INDEX idx_loan (loan_id),
        INDEX idx_date (reversion_date)
    )
");

// Find invalid payments with user information
$invalidPayments = DB::table('repayments as r')
    ->join('loan_schedules as ls', 'r.schedule_id', '=', 'ls.id')
    ->join('personal_loans as pl', 'r.loan_id', '=', 'pl.id')
    ->join('members as m', 'pl.member_id', '=', 'm.id')
    ->leftJoin('users as u', 'r.added_by', '=', 'u.id')
    ->select(
        'r.id', 
        'r.loan_id', 
        'r.amount', 
        'r.txn_id', 
        'r.date_created', 
        'r.added_by',
        'u.name as user_name',
        'm.fname', 
        'm.lname',
        'm.id as member_id',
        'pl.member_id',
        'ls.id as schedule_id', 
        'ls.paid', 
        'ls.principal', 
        'ls.interest'
    )
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
        printf("  %-30s | %2d payments | UGX %12.0f | %d loan(s)\n", 
            $member, $data['count'], $data['amount'], $loanCount);
    }
    echo str_repeat("─", 80) . "\n";
    printf("  %-30s | %2d payments | UGX %12.0f\n\n", 
        'TOTAL', count($invalidPayments), $totalAmount);
    
    echo "DETAILED LIST:\n";
    echo str_repeat("─", 150) . "\n";
    printf("%-6s | %-18s | %-12s | %-15s | %-20s | %-25s | %s\n", 
        "ID", "Member", "Amount", "Loan ID", "TXN ID", "Payment Date", "Initiated By");
    echo str_repeat("─", 150) . "\n";
    
    foreach ($invalidPayments as $p) {
        $initiatedBy = $p->user_name ?: 'System';
        printf("%-6d | %-18s | UGX %9.0f | %-15d | %-20s | %-25s | %s\n",
            $p->id,
            substr("{$p->fname} {$p->lname}", 0, 18),
            $p->amount,
            $p->loan_id,
            $p->txn_id ?: 'NULL',
            date('Y-m-d H:i:s', strtotime($p->date_created)),
            $initiatedBy
        );
    }
    echo str_repeat("─", 150) . "\n\n";
    
    // Confirmation
    echo "ACTION TO BE TAKEN:\n";
    echo "  1. Mark these repayments as INVALID (status = -1)\n";
    echo "  2. Reverse the paid amounts on schedules\n";
    echo "  3. Unmark schedules as paid if needed\n";
    echo "  4. Reopen loans if not all schedules are paid\n";
    echo "  5. Create audit trail records for compliance\n\n";
    
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
            $auditRecords = [];
            
            foreach ($invalidPayments as $p) {
                // Create audit trail before reverting
                $initiatedBy = $p->user_name ?: 'System';
                
                $auditRecord = [
                    'repayment_id' => $p->id,
                    'loan_id' => $p->loan_id,
                    'member_id' => $p->member_id,
                    'amount' => $p->amount,
                    'payment_date' => $p->date_created,
                    'initiated_by_user_id' => $p->added_by ?? 0,
                    'initiated_by_name' => $initiatedBy,
                    'txn_id' => $p->txn_id ?: 'NULL',
                    'reversion_date' => now(),
                    'reversion_reason' => 'Invalid mobile money - no FlexiPay transaction reference'
                ];
                
                DB::table('payment_reversion_audit')->insert($auditRecord);
                $auditRecords[] = $auditRecord;
                
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
                echo "✓ Fixed payment ID {$p->id}\n";
                echo "  Member: {$p->fname} {$p->lname} | Loan: {$p->loan_id} | Amount: " . 
                     number_format($p->amount, 0) . "\n";
                echo "  Initiated By: {$initiatedBy} | Date: " . 
                     date('Y-m-d H:i:s', strtotime($p->date_created)) . "\n";
                echo "  Audit Record Created ✓\n\n";
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
            echo "║     ✓ AUDIT TRAIL CREATED FOR COMPLIANCE                       ║\n";
            echo "╚════════════════════════════════════════════════════════════════╝\n";
            echo "\nSummary:\n";
            echo "  - Repayments Fixed: $fixedCount\n";
            echo "  - Loans Reopened: " . count(array_unique($affectedLoans)) . "\n";
            echo "  - Amount Reversed: UGX " . number_format($totalAmount, 0) . "\n";
            echo "  - Audit Records Created: " . count($auditRecords) . "\n\n";
            
            echo "AUDIT TRAIL SUMMARY:\n";
            echo str_repeat("─", 120) . "\n";
            echo "All reversion details have been logged to the 'payment_reversion_audit' table\n";
            echo "You can query the audit trail with:\n";
            echo "  SELECT * FROM payment_reversion_audit ORDER BY reversion_date DESC;\n\n";
            
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
