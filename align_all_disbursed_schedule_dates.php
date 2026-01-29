<?php

use App\Models\Disbursement;
use App\Models\GroupLoan;
use App\Models\GroupLoanSchedule;
use App\Models\LoanSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

function nextWorkingDay(Carbon $date): Carbon
{
    $next = $date->copy()->addDay();
    while ($next->isSunday()) {
        $next->addDay();
    }
    return $next;
}

$disbursements = Disbursement::where('status', 2)
    ->orderBy('created_at')
    ->get();

if ($disbursements->isEmpty()) {
    echo "No disbursed loans found.\n";
    exit(0);
}

$total = 0;
$aligned = 0;
$skipped = 0;
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

    if ($loan instanceof GroupLoan) {
        $schedules = GroupLoanSchedule::where('loan_id', $loan->id)
            ->orderBy('id')
            ->get();
    } else {
        $schedules = LoanSchedule::where('loan_id', $loan->id)
            ->orderBy('id')
            ->get();
    }

    if ($schedules->isEmpty()) {
        $missingSchedules++;
        continue;
    }

    // Build expected schedule dates based on disbursement date
    $expectedDates = [];
    $currentDate = $disbursementDate->copy();

    foreach ($schedules as $index => $schedule) {
        if ($periodType == '1') {
            $paymentDate = $currentDate->copy()->addWeek();
        } elseif ($periodType == '2') {
            $paymentDate = $currentDate->copy()->addMonthNoOverflow();
        } else {
            $paymentDate = nextWorkingDay($currentDate);
        }

        $expectedDates[] = $paymentDate;
        $currentDate = $paymentDate;
    }

    $firstActual = Carbon::parse($schedules[0]->payment_date)->toDateString();
    $firstExpected = $expectedDates[0]->toDateString();

    if ($firstActual === $firstExpected) {
        $skipped++;
        continue;
    }

    DB::beginTransaction();
    try {
        foreach ($schedules as $idx => $schedule) {
            $schedule->update([
                'payment_date' => $expectedDates[$idx]
            ]);
        }
        DB::commit();
        $aligned++;
        echo "Loan {$loan->id} schedule dates aligned.\n";
    } catch (Exception $e) {
        DB::rollBack();
        $skipped++;
        echo "Loan {$loan->id} failed: {$e->getMessage()}\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Disbursed loans checked: {$total}\n";
echo "Aligned schedules: {$aligned}\n";
echo "Skipped (already aligned): {$skipped}\n";
echo "Missing schedules: {$missingSchedules}\n";
echo "Skipped (missing product): {$skippedMissingProduct}\n";
