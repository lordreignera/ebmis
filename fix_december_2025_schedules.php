<?php

use App\Models\Disbursement;
use App\Models\GroupLoan;
use App\Models\Loan;
use App\Models\PersonalLoan;
use App\Models\LoanSchedule;
use App\Models\Repayment;
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
$fixed = 0;
$skippedWithRepayments = 0;
$missingLoans = 0;

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
        $missingLoans++;
        echo "Loan {$loanId} not found. Skipping.\n";
        continue;
    }

    $existingScheduleIds = LoanSchedule::where('loan_id', $loanId)->pluck('id')->all();

    $repaymentsCount = Repayment::whereIn('schedule_id', $existingScheduleIds)->count();
    if ($repaymentsCount > 0) {
        $skippedWithRepayments++;
        echo "Loan {$loanId} has repayments. Skipping to avoid data loss.\n";
        continue;
    }

    DB::beginTransaction();
    try {
        if (!empty($existingScheduleIds)) {
            DB::table('late_fees')->whereIn('schedule_id', $existingScheduleIds)->delete();
        }

        $service->generateAndSaveSchedule($loan);

        DB::commit();
        $fixed++;
        echo "Loan {$loanId} schedule regenerated.\n";
    } catch (Exception $e) {
        DB::rollBack();
        echo "Loan {$loanId} failed: {$e->getMessage()}\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Disbursements checked: {$total}\n";
echo "Schedules regenerated: {$fixed}\n";
echo "Skipped (repayments exist): {$skippedWithRepayments}\n";
echo "Missing loans: {$missingLoans}\n";
