<?php

use App\Models\Disbursement;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Models\PersonalLoan;
use App\Models\LoanSchedule;
use App\Services\LoanScheduleService;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

$start = '2025-12-01 00:00:00';
$end = '2025-12-31 23:59:59';

$disbursements = Disbursement::whereBetween('created_at', [$start, $end])->get();

if ($disbursements->isEmpty()) {
    echo "No disbursements found for December 2025.\n";
    exit(0);
}

$service = new LoanScheduleService();

$total = 0;
$updated = 0;
$skipped = 0;
$missing = 0;

foreach ($disbursements as $disbursement) {
    $loanId = $disbursement->loan_id;
    $total++;

    $loan = PersonalLoan::find($loanId);
    if (!$loan) {
        $loan = GroupLoan::find($loanId);
    }
    if (!$loan) {
        $loan = Loan::find($loanId);
    }

    if (!$loan) {
        $missing++;
        echo "Loan {$loanId} not found. Skipping.\n";
        continue;
    }

    $existingSchedules = LoanSchedule::where('loan_id', $loanId)
        ->orderBy('id')
        ->get();

    if ($existingSchedules->isEmpty()) {
        $skipped++;
        echo "Loan {$loanId} has no schedules. Skipping.\n";
        continue;
    }

    $newSchedules = $service->generateSchedule($loan);

    if ($existingSchedules->count() !== $newSchedules->count()) {
        $skipped++;
        echo "Loan {$loanId} schedule count mismatch (existing {$existingSchedules->count()} vs new {$newSchedules->count()}). Skipping.\n";
        continue;
    }

    DB::beginTransaction();
    try {
        foreach ($existingSchedules as $index => $schedule) {
            $newDate = $newSchedules[$index]['payment_date'];
            $schedule->update([
                'payment_date' => $newDate
            ]);
        }

        DB::commit();
        $updated++;
        echo "Loan {$loanId} schedule dates aligned.\n";
    } catch (Exception $e) {
        DB::rollBack();
        $skipped++;
        echo "Loan {$loanId} failed: {$e->getMessage()}\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Disbursements checked: {$total}\n";
echo "Schedules aligned: {$updated}\n";
echo "Skipped: {$skipped}\n";
echo "Missing loans: {$missing}\n";
