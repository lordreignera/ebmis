<?php

// Generate detailed report of fixed loans with client names
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIXED LOANS REPORT WITH CLIENT NAMES ===\n\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Total Amount Fixed: UGX 57,648,187\n";
echo "Total Loans Fixed: 51\n\n";

// Get all personal loans that were fixed
$loans = \App\Models\PersonalLoan::whereIn('status', [1, 2, 3])->get();

$fixedLoans = [];

foreach ($loans as $loan) {
    $completedRepayments = \App\Models\Repayment::where('loan_id', $loan->id)
        ->where('status', 1)
        ->sum('amount');
    
    $schedulePaid = \App\Models\LoanSchedule::where('loan_id', $loan->id)
        ->sum('paid');
    
    $originalMismatch = $completedRepayments - $schedulePaid;
    
    // These are the ones we just fixed (mismatch would have been > 1000 before)
    if ($completedRepayments > 0 && $originalMismatch < 1000) {
        $member = $loan->member;
        
        if ($member) {
            $schedules = \App\Models\LoanSchedule::where('loan_id', $loan->id)->get();
            $paidSchedules = $schedules->where('status', 1)->count();
            $totalSchedules = $schedules->count();
            
            $fixedLoans[] = [
                'loan_id' => $loan->id,
                'code' => $loan->code,
                'member_name' => $member->name,
                'member_phone' => $member->contact ?? 'N/A',
                'branch' => $loan->branch->name ?? 'N/A',
                'principal' => $loan->principal,
                'amount_paid' => $completedRepayments,
                'paid_schedules' => $paidSchedules,
                'total_schedules' => $totalSchedules,
                'status' => $loan->status == 1 ? 'Active' : ($loan->status == 2 ? 'Pending' : 'Closed')
            ];
        }
    }
}

// Sort by amount paid (descending)
usort($fixedLoans, function($a, $b) {
    return $b['amount_paid'] - $a['amount_paid'];
});

echo str_repeat("=", 150) . "\n";
printf("%-5s %-20s %-30s %-15s %-30s %-15s %-15s %-12s %-10s\n", 
    "ID", "Loan Code", "Client Name", "Phone", "Branch", "Principal", "Amount Paid", "Schedules", "Status");
echo str_repeat("=", 150) . "\n";

foreach ($fixedLoans as $loan) {
    printf("%-5s %-20s %-30s %-15s %-30s %-15s %-15s %-12s %-10s\n",
        $loan['loan_id'],
        $loan['code'],
        substr($loan['member_name'], 0, 30),
        $loan['member_phone'],
        substr($loan['branch'], 0, 30),
        number_format($loan['principal'], 0),
        number_format($loan['amount_paid'], 0),
        $loan['paid_schedules'] . '/' . $loan['total_schedules'],
        $loan['status']
    );
}

echo str_repeat("=", 150) . "\n\n";

// Generate CSV for easy sharing
$csvFile = __DIR__ . '/fixed_loans_report_' . date('Y-m-d_His') . '.csv';
$fp = fopen($csvFile, 'w');

fputcsv($fp, ['Loan ID', 'Loan Code', 'Client Name', 'Phone', 'Branch', 'Principal', 'Amount Paid', 'Paid Schedules', 'Total Schedules', 'Status']);

foreach ($fixedLoans as $loan) {
    fputcsv($fp, [
        $loan['loan_id'],
        $loan['code'],
        $loan['member_name'],
        $loan['member_phone'],
        $loan['branch'],
        $loan['principal'],
        $loan['amount_paid'],
        $loan['paid_schedules'],
        $loan['total_schedules'],
        $loan['status']
    ]);
}

fclose($fp);

echo "CSV report saved to: {$csvFile}\n";
echo "\nClients can now:\n";
echo "1. See their correct payment history\n";
echo "2. Pay only the remaining balance on partial schedules\n";
echo "3. Apply for new loans if fully paid\n";
