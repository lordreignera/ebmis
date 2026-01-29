<?php

use App\Models\Disbursement;
use App\Models\GroupLoan;
use App\Models\GroupLoanSchedule;
use App\Models\LoanSchedule;
use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$disbursements = Disbursement::where('status', 2)
    ->orderBy('created_at')
    ->get();

if ($disbursements->isEmpty()) {
    echo "No disbursed loans found.\n";
    exit(0);
}

$total = 0;
$correct = 0;
$wrong = 0;
$missingSchedules = 0;
$skippedMissingProduct = 0;

foreach ($disbursements as $disbursement) {
    $loan = $disbursement->loan;
    if (!$loan) {
        continue;
    }

    $total++;

    $periodType = $loan->product ? $loan->product->period_type : null;
    if (!$periodType) {
        $skippedMissingProduct++;
        continue;
    }

    $disbursementDate = $disbursement->disbursement_date
        ? Carbon::parse($disbursement->disbursement_date)
        : Carbon::parse($disbursement->created_at);

    if ($periodType == '1') {
        // Weekly: 7 days after disbursement
        $expected = $disbursementDate->copy()->addWeek();
    } elseif ($periodType == '2') {
        // Monthly: same day next month (no overflow)
        $expected = $disbursementDate->copy()->addMonthNoOverflow();
    } else {
        // Daily: next working day (skip Sundays)
        $expected = $disbursementDate->copy()->addDay();
        while ($expected->isSunday()) {
            $expected->addDay();
        }
    }

    if ($loan instanceof GroupLoan) {
        $firstSchedule = GroupLoanSchedule::where('loan_id', $loan->id)
            ->orderBy('payment_date')
            ->first();
    } else {
        $firstSchedule = LoanSchedule::where('loan_id', $loan->id)
            ->orderBy('payment_date')
            ->first();
    }

    if (!$firstSchedule) {
        $missingSchedules++;
        echo "Loan {$loan->id} has no schedules.\n";
        continue;
    }

    $actual = Carbon::parse($firstSchedule->payment_date);

    if ($actual->toDateString() !== $expected->toDateString()) {
        $wrong++;
        echo "Loan {$loan->id} mismatch: expected {$expected->toDateString()} vs actual {$actual->toDateString()}\n";
    } else {
        $correct++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Disbursed loans checked: {$total}\n";
echo "Correct first payments: {$correct}\n";
echo "Wrong first payments: {$wrong}\n";
echo "Missing schedules: {$missingSchedules}\n";
echo "Skipped (missing product): {$skippedMissingProduct}\n";
