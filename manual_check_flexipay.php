<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Repayment;
use App\Models\LoanSchedule;
use App\Models\Loan;

echo "Manually checking FlexiPay status for transaction EbP1762994337...\n\n";

// Get the mobile money service
$mobileMoneyService = app(\App\Services\MobileMoneyService::class);

$transactionRef = 'EbP1762994337';

try {
    $statusResult = $mobileMoneyService->checkTransactionStatus($transactionRef);
    
    echo "FlexiPay Status Result:\n";
    echo "Status: " . ($statusResult['status'] ?? 'unknown') . "\n";
    echo "Message: " . ($statusResult['message'] ?? 'N/A') . "\n";
    echo "\nFull Response:\n";
    print_r($statusResult);
    echo "\n\n";
    
    // If completed, update manually
    if ($statusResult['status'] === 'completed') {
        echo "Payment is COMPLETED on FlexiPay! Updating database...\n\n";
        
        $repayment = Repayment::where('transaction_reference', $transactionRef)->first();
        $schedule = LoanSchedule::find($repayment->schedule_id);
        $loan = Loan::find($repayment->loan_id);
        
        DB::beginTransaction();
        try {
            // Update repayment status
            $repayment->update([
                'payment_status' => 'Completed'
            ]);
            echo "✓ Updated repayment status to Completed\n";
            
            // Decrement pending_count
            if ($schedule->pending_count > 0) {
                $schedule->decrement('pending_count');
                echo "✓ Decremented pending_count\n";
            }
            
            // Update schedule paid amount
            $schedule->increment('paid', $repayment->amount);
            echo "✓ Incremented paid amount by {$repayment->amount}\n";
            
            // Refresh schedule
            $schedule->refresh();
            
            // Check if fully paid
            if ($schedule->paid >= $schedule->payment) {
                $schedule->update(['status' => 1]);
                echo "✓ Marked schedule as PAID\n";
            }
            
            // Check if loan is fully paid
            $totalPaid = Repayment::where('loan_id', $loan->id)
                                 ->where('payment_status', 'Completed')
                                 ->sum('amount');
            
            $totalDue = $loan->principal + $loan->interest;
            
            echo "\nLoan Status Check:\n";
            echo "Total Paid: {$totalPaid}\n";
            echo "Total Due: {$totalDue}\n";
            
            if ($totalPaid >= $totalDue) {
                $loan->update(['status' => 3]); // Completed
                echo "✓ Marked loan as COMPLETED\n";
            }
            
            DB::commit();
            echo "\n✓✓✓ ALL UPDATES COMPLETED SUCCESSFULLY!\n";
            echo "\nRefresh your browser to see the changes.\n";
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "ERROR during update: " . $e->getMessage() . "\n";
            echo $e->getTraceAsString() . "\n";
        }
        
    } else {
        echo "Payment status is: " . ($statusResult['status'] ?? 'unknown') . "\n";
        echo "No database updates needed.\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR checking FlexiPay status: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
