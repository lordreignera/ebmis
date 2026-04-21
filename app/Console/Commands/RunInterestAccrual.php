<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\LoanSchedule;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Carbon\Carbon;

class RunInterestAccrual extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *   php artisan accrual:run                  — accrues all due schedules up to today
     *   php artisan accrual:run --date=2026-04-01 — accrues up to a specific date
     *   php artisan accrual:run --dry-run         — preview without posting
     */
    protected $signature = 'accrual:run
                            {--date=    : Accrue up to this date (Y-m-d). Defaults to today.}
                            {--dry-run  : Preview what would be posted without creating journal entries.}';

    protected $description = 'MOP §3 Step 1 — Post interest accrual entries (DR Interest Receivable → CR Interest Income) for all due loan schedule instalments.';

    public function handle(AccountingService $accounting): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $isDryRun = $this->option('dry-run');

        $this->info('');
        $this->info('=== Interest Accrual Run ===');
        $this->info("Target date : {$targetDate->toDateString()}");
        $this->info("Mode        : " . ($isDryRun ? 'DRY RUN (no journals posted)' : 'LIVE'));
        $this->info('');

        // ── Find all due schedules that have NOT yet had an accrual posted ──
        // We join journal_entries to exclude already-accrued schedule ids.
        $alreadyAccrued = JournalEntry::where('reference_type', 'Interest Accrual')
            ->pluck('reference_id')
            ->all();

        $schedules = LoanSchedule::query()
            ->whereDate('payment_date', '<=', $targetDate->toDateString())
            ->where('interest', '>', 0)
            ->whereNotIn('id', $alreadyAccrued)
            // Only active / disbursed loans (status 2 = active in PersonalLoan)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('personal_loans')
                  ->whereColumn('personal_loans.id', 'loan_schedules.loan_id')
                  ->whereIn('personal_loans.status', [2, 'active', 'Active']);
            })
            ->with([
                'personalLoan' => function ($q) {
                    $q->with(['member', 'product']);
                },
            ])
            ->orderBy('payment_date')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No pending interest accruals found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$schedules->count()} schedule(s) to accrue.");
        $this->line('');

        $posted  = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($schedules as $schedule) {
            $loan = $schedule->personalLoan;

            if (!$loan) {
                $this->warn("  [SKIP] Schedule #{$schedule->id} — loan not found.");
                $skipped++;
                continue;
            }

            if (!$loan->product) {
                $this->warn("  [SKIP] Schedule #{$schedule->id} — loan #{$loan->id} has no product.");
                $skipped++;
                continue;
            }

            $borrowerName = $loan->member
                ? trim($loan->member->fname . ' ' . $loan->member->lname)
                : "Loan #{$loan->id}";

            $interest = (float) $schedule->interest;
            $dueDate  = $schedule->payment_date instanceof Carbon
                ? $schedule->payment_date->toDateString()
                : $schedule->payment_date;

            if ($isDryRun) {
                $this->line("  [DRY]  Schedule #{$schedule->id} | {$borrowerName} | {$loan->code} | due {$dueDate} | interest " . number_format($interest, 2));
                $posted++;
                continue;
            }

            try {
                $accrualDate = Carbon::parse($dueDate)->greaterThan($targetDate)
                    ? $targetDate
                    : Carbon::parse($dueDate);

                $journal = $accounting->postInterestAccrualEntry($loan, $schedule, $accrualDate);

                if ($journal) {
                    $this->line("  [OK]   Schedule #{$schedule->id} | {$borrowerName} | {$loan->code} | JE {$journal->journal_number} | UGX " . number_format($interest, 2));
                    $posted++;
                } else {
                    // Already accrued (duplicate guard returned null)
                    $skipped++;
                }
            } catch (\Exception $e) {
                $this->error("  [FAIL] Schedule #{$schedule->id} — {$e->getMessage()}");
                Log::error('accrual:run failed on schedule', [
                    'schedule_id' => $schedule->id,
                    'loan_id'     => $loan->id,
                    'error'       => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->line('');
        $this->info('=== Summary ===');
        $this->info("Posted  : {$posted}");
        $this->info("Skipped : {$skipped}");

        if ($failed > 0) {
            $this->error("Failed  : {$failed}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
