<?php
if (php_sapi_name() !== 'cli') { http_response_code(403); exit('Direct access not allowed.'); }

/**
 * =====================================================================
 * RECOVER MISSING JOURNAL ENTRIES (Last 3 Months)
 * =====================================================================
 * Rebuilds missing GL entries from repayments and disbursements whose
 * dates fall within a configurable window (default: last 3 months).
 *
 * Usage:
 *   php recover_missing_journals.php            -- dry run (preview only)
 *   php recover_missing_journals.php --commit   -- write to database
 *   php recover_missing_journals.php --commit --from=2026-01-01 --to=2026-03-31
 * =====================================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Disbursement;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\Repayment;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// ── CLI options ───────────────────────────────────────────────────────
$args    = array_slice($argv ?? [], 1);
$commit  = in_array('--commit', $args, true);

$fromArg = null;
$toArg   = null;
foreach ($args as $arg) {
    if (str_starts_with($arg, '--from=')) $fromArg = substr($arg, 7);
    if (str_starts_with($arg, '--to='))   $toArg   = substr($arg, 5);
}

$fromDate = $fromArg ? Carbon::parse($fromArg)->startOfDay() : Carbon::now()->subMonths(3)->startOfDay();
$toDate   = $toArg   ? Carbon::parse($toArg)->endOfDay()     : Carbon::now()->endOfDay();

// ── Header ───────────────────────────────────────────────────────────
echo "\n";
echo "=================================================================\n";
echo "  RECOVER MISSING JOURNAL ENTRIES\n";
echo "=================================================================\n";
echo "  Date window : " . $fromDate->format('Y-m-d') . " → " . $toDate->format('Y-m-d') . "\n";
echo "  Mode        : " . ($commit ? "*** COMMIT (writing to DB) ***" : "DRY RUN (no changes)") . "\n";
echo "=================================================================\n\n";

if (!$commit) {
    echo "  ℹ  Run with --commit to actually write entries.\n\n";
}

$accountingService = new AccountingService();

$stats = [
    'disb_found'    => 0,
    'disb_skipped'  => 0,
    'disb_created'  => 0,
    'disb_errors'   => 0,
    'rep_found'     => 0,
    'rep_skipped'   => 0,
    'rep_created'   => 0,
    'rep_errors'    => 0,
];

// ─────────────────────────────────────────────────────────────────────
// 1. DISBURSEMENTS
// ─────────────────────────────────────────────────────────────────────
echo "─── DISBURSEMENTS ───────────────────────────────────────────────\n";

// Build a set of disbursement IDs that already have a journal entry
$alreadyPostedDisbIds = JournalEntry::where('reference_type', 'Disbursement')
    ->pluck('reference_id')
    ->flip();          // use as a hash-set for O(1) lookup

// Get disbursements with an approved/disbursed status within the date window.
// date_approved and created_at are the date columns available in this DB.
$disbursements = Disbursement::query()
    ->where(function ($q) use ($fromDate, $toDate) {
        $q->whereBetween('date_approved', [$fromDate, $toDate])
          ->orWhereBetween('created_at',   [$fromDate, $toDate]);
    })
    ->where('amount', '>', 0)
    ->whereIn('status', [1, 2])   // 1=Approved, 2=Disbursed
    ->orderByRaw("COALESCE(date_approved, created_at)")
    ->get();

echo "  Found " . $disbursements->count() . " disbursement(s) in window.\n\n";

foreach ($disbursements as $disb) {
    $stats['disb_found']++;

    // Determine display date
    $displayDate = $disb->date_approved
        ?? $disb->created_at
        ?? 'unknown date';
    $displayDate = is_object($displayDate) ? $displayDate->format('Y-m-d') : $displayDate;

    // Resolve loan label
    $loan = ($disb->loan_type == 2)
        ? GroupLoan::with(['group', 'product'])->find($disb->loan_id)
        : PersonalLoan::with(['member', 'product'])->find($disb->loan_id);

    $loanLabel = $loan ? ($loan->code ?? "Loan#{$disb->loan_id}") : "Loan#{$disb->loan_id}";
    $borrower  = $loan
        ? (isset($loan->member)
            ? ($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? '')
            : ($loan->group->name ?? 'Group'))
        : 'Unknown';

    // Already posted?
    if (isset($alreadyPostedDisbIds[$disb->id])) {
        echo "  [SKIP] Disb #{$disb->id} | {$displayDate} | {$loanLabel} | {$borrower} — JE already exists\n";
        $stats['disb_skipped']++;
        continue;
    }

    echo "  [" . ($commit ? "POST" : "WOULD POST") . "] Disb #{$disb->id} | {$displayDate} | {$loanLabel} | {$borrower} | UGX " . number_format($disb->amount, 2);

    if ($commit) {
        try {
            DB::beginTransaction();
            $journal = $accountingService->postDisbursementEntry($disb);
            DB::commit();
            if ($journal) {
                echo " → {$journal->journal_number}\n";
                $stats['disb_created']++;
            } else {
                echo " → [AccountingService returned null – check accounts setup]\n";
                $stats['disb_errors']++;
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            echo " → ERROR: " . $e->getMessage() . "\n";
            $stats['disb_errors']++;
        }
    } else {
        echo " → [dry run]\n";
        $stats['disb_created']++;   // count as "would create"
    }
}

// ─────────────────────────────────────────────────────────────────────
// 2. REPAYMENTS
// ─────────────────────────────────────────────────────────────────────
echo "\n─── REPAYMENTS ──────────────────────────────────────────────────\n";

$alreadyPostedRepIds = JournalEntry::where('reference_type', 'Repayment')
    ->pluck('reference_id')
    ->flip();

// Repayments use date_created column
$repayments = Repayment::query()
    ->where(function ($q) use ($fromDate, $toDate) {
        $q->whereBetween('date_created', [$fromDate, $toDate]);
    })
    ->where('amount', '>', 0)
    ->where('status', 1)               // Confirmed only
    ->orderBy('date_created')
    ->with(['loan', 'loan.member', 'loan.product', 'schedule'])
    ->get();

echo "  Found " . $repayments->count() . " confirmed repayment(s) in window.\n\n";

foreach ($repayments as $rep) {
    $stats['rep_found']++;

    $displayDate = $rep->date_created ?? 'unknown';
    $displayDate = is_object($displayDate) ? $displayDate->format('Y-m-d') : $displayDate;

    $loan     = $rep->loan;
    $loanLabel = $loan ? ($loan->code ?? "Loan#{$rep->loan_id}") : "Loan#{$rep->loan_id}";
    $borrower  = ($loan && $loan->member)
        ? ($loan->member->fname ?? '') . ' ' . ($loan->member->lname ?? '')
        : 'Unknown';

    if (isset($alreadyPostedRepIds[$rep->id])) {
        echo "  [SKIP] Rep #{$rep->id} | {$displayDate} | {$loanLabel} | {$borrower} — JE already exists\n";
        $stats['rep_skipped']++;
        continue;
    }

    echo "  [" . ($commit ? "POST" : "WOULD POST") . "] Rep #{$rep->id} | {$displayDate} | {$loanLabel} | {$borrower} | UGX " . number_format($rep->amount, 2);

    if (!$loan) {
        echo " → SKIP – loan not found\n";
        $stats['rep_errors']++;
        continue;
    }

    if ($commit) {
        try {
            DB::beginTransaction();
            $journal = $accountingService->postRepaymentEntry($rep, $loan);
            DB::commit();
            if ($journal) {
                echo " → {$journal->journal_number}\n";
                $stats['rep_created']++;
            } else {
                echo " → [AccountingService returned null – check accounts setup]\n";
                $stats['rep_errors']++;
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            echo " → ERROR: " . $e->getMessage() . "\n";
            $stats['rep_errors']++;
        }
    } else {
        echo " → [dry run]\n";
        $stats['rep_created']++;
    }
}

// ─────────────────────────────────────────────────────────────────────
// SUMMARY
// ─────────────────────────────────────────────────────────────────────
$mode = $commit ? 'COMMITTED' : 'DRY RUN';
echo "\n";
echo "=================================================================\n";
echo "  SUMMARY  ({$mode})\n";
echo "=================================================================\n";
echo str_pad('Disbursements in window:',   36) . $stats['disb_found']   . "\n";
echo str_pad('  Already had JE (skipped):', 36) . $stats['disb_skipped'] . "\n";
echo str_pad('  ' . ($commit ? 'Created' : 'Would create') . ':',        36) . $stats['disb_created'] . "\n";
echo str_pad('  Errors:',                  36) . $stats['disb_errors']  . "\n";
echo "\n";
echo str_pad('Repayments in window:',      36) . $stats['rep_found']    . "\n";
echo str_pad('  Already had JE (skipped):', 36) . $stats['rep_skipped']  . "\n";
echo str_pad('  ' . ($commit ? 'Created' : 'Would create') . ':',        36) . $stats['rep_created']  . "\n";
echo str_pad('  Errors:',                  36) . $stats['rep_errors']   . "\n";
echo "=================================================================\n\n";

if (!$commit && ($stats['disb_created'] + $stats['rep_created']) > 0) {
    echo "  Run with --commit to write " . ($stats['disb_created'] + $stats['rep_created']) . " journal entries.\n\n";
}

echo "Done.\n";
