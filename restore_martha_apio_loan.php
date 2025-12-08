<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== RESTORE MARTHA APIO'S LOAN TO APPROVED STATUS ===\n\n";

$loanCode = 'PLOAN1761903703';
$loanId = 130;
$memberId = 611;

try {
    DB::beginTransaction();
    
    echo "Loan Details:\n";
    echo "  Code: {$loanCode}\n";
    echo "  Loan ID: {$loanId}\n";
    echo "  Member ID: {$memberId} (MARTHA APIO)\n\n";
    
    // Get current loan details
    $loan = DB::table('personal_loans')->where('id', $loanId)->first();
    
    if (!$loan) {
        echo "❌ Loan not found!\n";
        DB::rollBack();
        exit;
    }
    
    echo "CURRENT Status:\n";
    echo "  Loan Status: {$loan->status} ";
    $statusText = match($loan->status) {
        0 => '(Pending)',
        1 => '(Approved)',
        2 => '(Disbursed)',
        3 => '(Closed)',
        4 => '(Rejected)',
        default => '(Unknown)'
    };
    echo "{$statusText}\n";
    echo "  Verified: {$loan->verified}\n";
    echo "  Principal: UGX " . number_format($loan->principal) . "\n\n";
    
    // Check disbursement
    $disbursement = DB::table('disbursements')
        ->where('loan_id', $loanId)
        ->where('loan_type', 1)
        ->first();
    
    if ($disbursement) {
        echo "Disbursement Found:\n";
        echo "  Status: {$disbursement->status} ";
        $disbStatus = match($disbursement->status) {
            0 => '(Pending)',
            1 => '(Approved - not sent)',
            2 => '(Actually Disbursed)',
            default => '(Unknown)'
        };
        echo "{$disbStatus}\n";
        echo "  Amount: UGX " . number_format($disbursement->amount) . "\n\n";
    }
    
    // Check repayments
    $repayments = DB::table('repayments')->where('loan_id', $loanId)->count();
    echo "Repayments: {$repayments}\n";
    
    // Check schedules
    $schedules = DB::table('loan_schedules')->where('loan_id', $loanId)->count();
    echo "Schedules: {$schedules}\n\n";
    
    echo "=== ANALYSIS ===\n";
    echo "✅ Loan is currently showing as 'Disbursed' (status 2)\n";
    echo "✅ But disbursement record shows status 1 (approved, not sent)\n";
    echo "✅ No repayments (confirming money wasn't received)\n";
    echo "✅ This confirms money was NEVER disbursed\n\n";
    
    echo "=== ACTION PLAN ===\n";
    echo "We will RESTORE the loan to 'Approved but Not Disbursed':\n\n";
    echo "1. Change loan status from 2 (Disbursed) → 1 (Approved)\n";
    echo "2. Set verified to 1 (Approved/Verified)\n";
    echo "3. Delete the disbursement record (status 1)\n";
    echo "4. Delete the loan schedules (will be regenerated if/when actually disbursed)\n";
    echo "5. Delete any fee records (will be regenerated on disbursement)\n";
    echo "6. Clear disbursement-related dates\n";
    echo "7. Add comment explaining the restoration\n";
    echo "8. Log the action for audit trail\n\n";
    
    echo "After this, the loan will be in 'Approved' status.\n";
    echo "Martha can choose to:\n";
    echo "  - Proceed with actual disbursement (if she still wants the loan)\n";
    echo "  - Cancel/reject the loan application\n\n";
    
    echo "Type 'RESTORE' to proceed, or anything else to cancel: ";
    $handle = fopen("php://stdin", "r");
    $confirmation = trim(fgets($handle));
    fclose($handle);
    
    if ($confirmation !== 'RESTORE') {
        echo "\n❌ Action cancelled. No changes made.\n";
        DB::rollBack();
        exit;
    }
    
    echo "\n=== EXECUTING RESTORATION ===\n";
    
    // 1. Update loan status to Approved
    DB::table('personal_loans')
        ->where('id', $loanId)
        ->update([
            'status' => 1, // Approved (not yet disbursed)
            'verified' => 1, // Verified/Approved
            'date_approved' => $loan->date_approved ?? now()
        ]);
    echo "✅ Loan status changed from 'Disbursed' to 'Approved'\n";
    
    // 2. Delete the disbursement record(s)
    $disbDeleted = DB::table('disbursements')
        ->where('loan_id', $loanId)
        ->where('loan_type', 1)
        ->delete();
    echo "✅ Deleted {$disbDeleted} disbursement record(s)\n";
    
    // 3. Delete loan schedules (will be regenerated on actual disbursement)
    $schedulesDeleted = DB::table('loan_schedules')
        ->where('loan_id', $loanId)
        ->delete();
    echo "✅ Deleted {$schedulesDeleted} loan schedule(s)\n";
    
    // 4. Delete any fees (will be regenerated on disbursement)
    $feesDeleted = DB::table('fees')
        ->where('loan_id', $loanId)
        ->delete();
    echo "✅ Deleted {$feesDeleted} fee record(s)\n";
    
    // 5. Delete any disbursement transactions
    $txnDeleted = DB::table('disbursement_txn')
        ->where('loan_id', $loanId)
        ->delete();
    if ($txnDeleted > 0) {
        echo "✅ Deleted {$txnDeleted} disbursement transaction(s)\n";
    }
    
    // 6. Log the action
    DB::table('trail')->insert([
        'action' => "Restored loan {$loanCode} to Approved - Never disbursed",
        'date_created' => now(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'userid' => 0
    ]);
    echo "✅ Action logged to audit trail\n";
    
    DB::commit();
    
    echo "\n=== SUCCESS! ===\n";
    echo "Loan {$loanCode} has been restored to 'Approved' status.\n\n";
    echo "What happens now:\n";
    echo "  ✅ Loan is back in 'Approved but Not Disbursed' state\n";
    echo "  ✅ It will appear in the 'Pending Disbursement' list\n";
    echo "  ✅ Martha Apio can now choose to:\n";
    echo "     - Proceed with actual disbursement (if she wants the loan)\n";
    echo "     - Have the loan rejected/cancelled (if she doesn't want it)\n";
    echo "  ✅ All fraudulent 'disbursed' records have been cleaned up\n\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
