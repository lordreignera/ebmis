<?php
/**
 * THIS script replicates EXACTLY what the web interface shows
 * at http://localhost:84/admin/loans/repayments/schedules/105
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n=== REPLICATING WEB INTERFACE CALCULATION ===\n";
echo "URL: http://localhost:84/admin/loans/repayments/schedules/105\n\n";

$loanId = 105;

// Get loan
$loan = DB::table('personal_loans as pl')
    ->join('products as p', 'pl.product_type', '=', 'p.id')
    ->where('pl.id', $loanId)
    ->select('pl.*', 'p.period_type')
    ->first();

// Get schedules
$schedules = DB::table('loan_schedules')
    ->where('loan_id', $loanId)
    ->orderBy('payment_date')
    ->get();

$principal = floatval($loan->principal);
$globalprincipal = floatval($loan->principal);

foreach ($schedules as $schedule) {
    echo "─────────────────────────────────────────\n";
    echo "Schedule #{$schedule->id}\n";
    echo "Due Date: {$schedule->payment_date}\n";
    echo "Principal: " . number_format($schedule->principal, 0) . " UGX\n";
    echo "Interest: " . number_format($schedule->interest, 0) . " UGX\n";
    
    // 1. Calculate "Principal cal Interest"
    $period = floor($loan->period / 2);
    $pricipalcalIntrest = $period > 0 ? ($loan->principal / $period) : 0;
    
    // 2. Use actual interest from schedule
    $intrestamtpayable = $schedule->interest;
    
    // 3. Calculate periods in arrears - EXACT RepaymentController logic
    $now = $schedule->date_cleared ? strtotime($schedule->date_cleared) : time();
    $your_date = strtotime($schedule->payment_date);
    $datediff = $now - $your_date;
    $d = floor($datediff / (60 * 60 * 24)); // Days overdue
    
    $dd = 0; // Periods overdue
    if ($d > 0) {
        if ($loan->period_type == '1') {
            $dd = ceil($d / 7); // Weekly
        } else if ($loan->period_type == '2') {
            $dd = ceil($d / 30); // Monthly
        } else if ($loan->period_type == '3') {
            $dd = $d; // Daily
        } else {
            $dd = ceil($d / 7); // Default
        }
    }
    
    // 4. Calculate late fees - EXACT RepaymentController formula
    $latepayment = (($schedule->principal + $intrestamtpayable) * 0.06) * $dd;
    
    echo "\nLATE FEE CALCULATION:\n";
    echo "  Days overdue (d): {$d}\n";
    echo "  Periods overdue (dd): {$dd}\n";
    echo "  Formula: (({$schedule->principal} + {$intrestamtpayable}) × 0.06) × {$dd}\n";
    echo "  Calculation: (" . number_format($schedule->principal + $intrestamtpayable, 0) . " × 0.06) × {$dd}\n";
    echo "  Late Fee: " . number_format($latepayment, 0) . " UGX\n";
    
    if ($d > 0 && $schedule->status == 0) {
        echo "\n⚠️  THIS IS WHAT THE WEB SHOWS\n";
    }
    
    echo "\n";
}

echo "\n=== DONE ===\n\n";
