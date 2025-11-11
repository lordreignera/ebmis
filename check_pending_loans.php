<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PersonalLoan;

echo "Checking pending loans for Nakamatte Norah...\n\n";

$pendingLoans = PersonalLoan::where('member_id', 612)
                            ->where('status', 0) // Pending
                            ->orderBy('id', 'desc')
                            ->get();

if ($pendingLoans->count() > 0) {
    echo "Found " . $pendingLoans->count() . " pending loan(s):\n\n";
    
    foreach ($pendingLoans as $loan) {
        echo "Loan ID: " . $loan->id . "\n";
        echo "Code: " . $loan->code . "\n";
        echo "Principal: UGX " . number_format($loan->principal) . "\n";
        echo "Period: " . $loan->period . " days\n";
        echo "Interest: " . $loan->interest . "%\n";
        echo "Status: " . ($loan->status == 0 ? 'Pending Approval' : 'Other') . "\n";
        echo "Created: " . $loan->datecreated . "\n";
        echo "---\n";
    }
} else {
    echo "No pending loans found\n";
}
