<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Direct access not allowed.'); }

/**
 * =====================================================================
 * RECREATE ALL JOURNAL ENTRIES
 * =====================================================================
 * Wipes every existing journal entry then rebuilds the full GL ledger
 * from source transactions in chronological order, preserving the
 * original transaction dates recorded on each disbursement / fee /
 * repayment record.
 *
 * Processing order (important for running_balance accuracy):
 *   1. Disbursements  — creates Loan Receivable assets
 *   2. Fee Collections — income entries pre-/post-disbursement
 *   3. Interest Accruals — DR Interest Receivable → CR Interest Income
 *   4. Repayments    — clears IR (step 3a) and Loan Receivable
 *
 * Usage:
 *   php recreate_all_journals.php              -- dry run (no changes)
 *   php recreate_all_journals.php --commit     -- DELETES OLD + RECREATES
 * =====================================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Disbursement;
use App\Models\Fee;
use App\Models\FeeType;
use App\Models\JournalEntry;
use App\Models\LoanSchedule;
use App\Models\PersonalLoan;
use App\Models\SystemAccount;
use App\Models\Repayment;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// ── CLI flags ─────────────────────────────────────────────────────────
$args   = array_slice($argv ?? [], 1);
$commit = in_array('--commit', $args, true);

// ── Banner ────────────────────────────────────────────────────────────
echo "\n";
echo "=================================================================\n";
echo "  RECREATE ALL JOURNAL ENTRIES\n";
echo "=================================================================\n";
echo "  Mode : " . ($commit ? '*** COMMIT — will DELETE existing journals ***' : 'DRY RUN (no changes)') . "\n";
echo "  Date : " . Carbon::today()->toDateString() . "\n";
echo "=================================================================\n\n";

if (!$commit) {
    echo "  Run with --commit to delete old entries and recreate.\n\n";
}

// ── Stats ─────────────────────────────────────────────────────────────
$stats = [
    'deleted'         => 0,
    'disb_ok'         => 0,
    'disb_skip'       => 0,
    'disb_err'        => 0,
    'fee_ok'          => 0,
    'fee_skip'        => 0,
    'fee_err'         => 0,
    'accrual_ok'      => 0,
    'accrual_skip'    => 0,
    'accrual_err'     => 0,
    'rep_ok'          => 0,
    'rep_skip'        => 0,
    'rep_err'         => 0,
];

$accountingService = new AccountingService();

// ─────────────────────────────────────────────────────────────────────
// STEP 0 — Show current state
// ─────────────────────────────────────────────────────────────────────
$existingCount   = DB::table('journal_entries')->count();
$disbCount       = Disbursement::count();
$feeCount        = Fee::where('payment_status', 'SUCCESS')->orWhere('payment_status', 'Paid')->count();
$accrualCount    = LoanSchedule::whereDate('payment_date', '<=', Carbon::today())
    ->where('interest', '>', 0)
    ->whereExists(fn($q) => $q->select(DB::raw(1))
        ->from('personal_loans')
        ->whereColumn('personal_loans.id', 'loan_schedules.loan_id')
        ->whereIn('personal_loans.status', [2, 'active', 'Active']))
    ->count();
$repCount        = Repayment::where('status', 1)->count();

echo "Current state:\n";
echo "  Existing journal entries : {$existingCount}\n";
echo "  Disbursements to process : {$disbCount}\n";
echo "  Paid fees to process     : {$feeCount}\n";
echo "  Accrual schedules pending: {$accrualCount}\n";
echo "  Confirmed repayments     : {$repCount}\n\n";

if (!$commit) {
    echo "─── DRY RUN COMPLETE — no changes made ───\n\n";
    exit(0);
}

// ─────────────────────────────────────────────────────────────────────
// STEP 1 — Delete all existing journals (lines cascade)
// ─────────────────────────────────────────────────────────────────────
echo "Step 1: Deleting existing journal entries…\n";

DB::statement('SET FOREIGN_KEY_CHECKS=0');
$deleted = DB::table('journal_lines')->delete();
echo "  Deleted {$deleted} journal lines.\n";
$deleted = DB::table('journal_entries')->delete();
echo "  Deleted {$deleted} journal entries.\n";
$stats['deleted'] = $deleted;

// Reset account running balances so they're rebuilt from scratch
DB::table('system_accounts')->update(['running_balance' => 0]);
echo "  Reset running_balance = 0 on all system accounts.\n";
DB::statement('SET FOREIGN_KEY_CHECKS=1');

echo "  Done.\n\n";

// ─────────────────────────────────────────────────────────────────────
// STEP 2 — Disbursements
// ─────────────────────────────────────────────────────────────────────
echo "Step 2: Recreating disbursement journals…\n";

$disbursements = Disbursement::orderByRaw("COALESCE(date_approved, created_at)")->get();

foreach ($disbursements as $disb) {
    $label = "Disb #{$disb->id}";
    try {
        DB::beginTransaction();
        $journal = $accountingService->postDisbursementEntry($disb);
        DB::commit();

        if ($journal) {
            echo "  [OK]  {$label} → {$journal->journal_number}\n";
            $stats['disb_ok']++;
        } else {
            echo "  [SKIP] {$label} → postDisbursementEntry returned null\n";
            $stats['disb_skip']++;
        }
    } catch (\Exception $e) {
        DB::rollBack();
        echo "  [ERR]  {$label} → " . $e->getMessage() . "\n";
        $stats['disb_err']++;
    }
}

echo "  Done. OK:{$stats['disb_ok']}  Skip:{$stats['disb_skip']}  Err:{$stats['disb_err']}\n\n";

// ─────────────────────────────────────────────────────────────────────
// STEP 3 — Fee Collections
// ─────────────────────────────────────────────────────────────────────
echo "Step 3: Recreating fee collection journals…\n";

$fees = Fee::with('feeType')
    ->where(function ($q) {
        $q->where('payment_status', 'SUCCESS')
          ->orWhere('payment_status', 'Paid')
          ->orWhere('payment_status', 'paid');
    })
    ->orderByRaw("COALESCE(datecreated, id)")
    ->get();

foreach ($fees as $fee) {
    $label = "Fee #{$fee->id}";
    try {
        DB::beginTransaction();
        $journal = $accountingService->postFeeCollectionEntry($fee, $fee->feeType);
        DB::commit();

        if ($journal) {
            echo "  [OK]  {$label} (UGX " . number_format($fee->amount, 0) . ") → {$journal->journal_number}\n";
            $stats['fee_ok']++;
        } else {
            echo "  [SKIP] {$label} → returned null\n";
            $stats['fee_skip']++;
        }
    } catch (\Exception $e) {
        DB::rollBack();
        echo "  [ERR]  {$label} → " . $e->getMessage() . "\n";
        $stats['fee_err']++;
    }
}

echo "  Done. OK:{$stats['fee_ok']}  Skip:{$stats['fee_skip']}  Err:{$stats['fee_err']}\n\n";

// ─────────────────────────────────────────────────────────────────────
// STEP 4 — Interest Accruals
// ─────────────────────────────────────────────────────────────────────
echo "Step 4: Recreating interest accrual journals…\n";

$schedules = LoanSchedule::query()
    ->whereDate('payment_date', '<=', Carbon::today())
    ->where('interest', '>', 0)
    ->whereExists(fn($q) => $q->select(DB::raw(1))
        ->from('personal_loans')
        ->whereColumn('personal_loans.id', 'loan_schedules.loan_id')
        ->whereIn('personal_loans.status', [2, 'active', 'Active']))
    ->with(['personalLoan' => fn($q) => $q->with(['member', 'product'])])
    ->orderBy('payment_date')
    ->get();

foreach ($schedules as $schedule) {
    $loan  = $schedule->personalLoan;
    $label = "Schedule #{$schedule->id}";

    if (!$loan || !$loan->product) {
        echo "  [SKIP] {$label} — loan or product not found\n";
        $stats['accrual_skip']++;
        continue;
    }

    try {
        DB::beginTransaction();
        $accrualDate = Carbon::parse($schedule->payment_date);
        if ($accrualDate->greaterThan(Carbon::today())) {
            $accrualDate = Carbon::today();
        }

        $journal = $accountingService->postInterestAccrualEntry($loan, $schedule, $accrualDate);
        DB::commit();

        if ($journal) {
            echo "  [OK]  {$label} | {$loan->code} | due {$schedule->payment_date} → {$journal->journal_number}\n";
            $stats['accrual_ok']++;
        } else {
            echo "  [SKIP] {$label} → returned null (duplicate guard?)\n";
            $stats['accrual_skip']++;
        }
    } catch (\Exception $e) {
        DB::rollBack();
        echo "  [ERR]  {$label} → " . $e->getMessage() . "\n";
        $stats['accrual_err']++;
    }
}

echo "  Done. OK:{$stats['accrual_ok']}  Skip:{$stats['accrual_skip']}  Err:{$stats['accrual_err']}\n\n";

// ─────────────────────────────────────────────────────────────────────
// STEP 5 — Repayments
// ─────────────────────────────────────────────────────────────────────
echo "Step 5: Recreating repayment journals…\n";

$repayments = Repayment::where('status', 1)
    ->orderByRaw("COALESCE(date_created, id)")
    ->get();

foreach ($repayments as $rep) {
    $label = "Rep #{$rep->id}";
    $loan  = PersonalLoan::with(['member', 'product', 'branch'])->find($rep->loan_id);

    if (!$loan) {
        echo "  [SKIP] {$label} → loan #{$rep->loan_id} not found\n";
        $stats['rep_skip']++;
        continue;
    }

    try {
        DB::beginTransaction();
        $journal = $accountingService->postRepaymentEntry($rep, $loan);
        DB::commit();

        if ($journal) {
            $borrower = trim(($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? ''));
            echo "  [OK]  {$label} | {$borrower} | {$loan->code} | UGX " . number_format($rep->amount, 0) . " → {$journal->journal_number}\n";
            $stats['rep_ok']++;
        } else {
            echo "  [SKIP] {$label} → returned null\n";
            $stats['rep_skip']++;
        }
    } catch (\Exception $e) {
        DB::rollBack();
        echo "  [ERR]  {$label} → " . $e->getMessage() . "\n";
        $stats['rep_err']++;
    }
}

echo "  Done. OK:{$stats['rep_ok']}  Skip:{$stats['rep_skip']}  Err:{$stats['rep_err']}\n\n";

// ─────────────────────────────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────────────────────────────
$totalNew = DB::table('journal_entries')->count();

echo "=================================================================\n";
echo "  RECREATE COMPLETE\n";
echo "=================================================================\n";
echo "  Old entries deleted   : {$stats['deleted']}\n";
echo "  New entries created   : {$totalNew}\n";
echo "  ─────────────────────────────────────────\n";
echo "  Disbursements  OK/Skip/Err : {$stats['disb_ok']} / {$stats['disb_skip']} / {$stats['disb_err']}\n";
echo "  Fees           OK/Skip/Err : {$stats['fee_ok']} / {$stats['fee_skip']} / {$stats['fee_err']}\n";
echo "  Accruals       OK/Skip/Err : {$stats['accrual_ok']} / {$stats['accrual_skip']} / {$stats['accrual_err']}\n";
echo "  Repayments     OK/Skip/Err : {$stats['rep_ok']} / {$stats['rep_skip']} / {$stats['rep_err']}\n";
echo "=================================================================\n\n";
