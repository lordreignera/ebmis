<?php

/**
 * Backfill Journal Entries for Historical Loans
 * 
 * This script creates journal entries for loans that were created before
 * the accounting system was implemented.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Loan;
use App\Models\JournalEntry;
use App\Models\Repayment;
use App\Models\Disbursement;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;

echo "=================================================\n";
echo "BACKFILL JOURNAL ENTRIES FOR HISTORICAL LOANS\n";
echo "=================================================\n\n";

// prepare log file
$logFile = __DIR__ . '/storage/logs/backfill_loan_journals.log';
if (!is_dir(dirname($logFile))) {
    @mkdir(dirname($logFile), 0755, true);
}

function logMsg($msg)
{
    global $logFile;
    $time = date('Y-m-d H:i:s');
    $line = "[{$time}] " . $msg . "\n";
    echo $line;
    @file_put_contents($logFile, $line, FILE_APPEND);
}

// Get all loans that have disbursements
$loans = Loan::whereHas('disbursements')
    ->orderBy('id')
    ->get();

echo "Found " . $loans->count() . " loans to process.\n\n";

$accountingService = new AccountingService();
// initialize stats
$stats = [
    'total_loans' => $loans->count(),
    'disbursements_created' => 0,
    'disbursements_skipped' => 0,
    'repayments_created' => 0,
    'repayments_skipped' => 0,
    'errors' => 0,
];

foreach ($loans as $loan) {
    try {
        logMsg("-------------------------------------------");
        logMsg("Processing Loan ID: {$loan->id} | Code: {$loan->code}");
        logMsg("Member: " . ($loan->member ? $loan->member->name : ($loan->group ? $loan->group->name : 'N/A')));
        logMsg("Principal: UGX " . number_format($loan->principal, 2));

        // Get all disbursements for this loan
        $disbursements = Disbursement::where('loan_id', $loan->id)
            ->where('amount', '>', 0)
            ->orderBy('date_approved')
            ->get();

        if ($disbursements->count() > 0) {
            logMsg("Processing {$disbursements->count()} disbursement(s)...");

            foreach ($disbursements as $disbursement) {
                // Check if disbursement journal entry already exists
                $disbursementJournal = JournalEntry::where('reference_type', 'Disbursement')
                    ->where('reference_id', $disbursement->id)
                    ->first();

                if ($disbursementJournal) {
                    logMsg("Disbursement #{$disbursement->id} journal already exists (JE: {$disbursementJournal->journal_number})");
                    $stats['disbursements_skipped']++;
                } else {
                    try {
                        logMsg("Creating disbursement journal for amount: UGX " . number_format($disbursement->amount, 2) . "...");
                        $disbDate = $disbursement->date_approved ? $disbursement->date_approved->format('Y-m-d') : ($disbursement->created_at ? $disbursement->created_at->format('Y-m-d') : date('Y-m-d'));
                        logMsg("  Date: {$disbDate}");

                        $journalEntry = $accountingService->postDisbursementEntry($disbursement);

                        if ($journalEntry) {
                            // Append disbursement metadata to narrative (who disbursed / assigned / method)
                            $disbBy = 'N/A';
                            if ($disbursement->addedBy) {
                                $disbBy = trim(($disbursement->addedBy->fname ?? '') . ' ' . ($disbursement->addedBy->lname ?? '')) ?: ($disbursement->added_by ?? 'N/A');
                            } elseif ($disbursement->added_by) {
                                $disbBy = $disbursement->added_by;
                            }

                            $assignedTo = 'N/A';
                            if ($disbursement->assignedTo) {
                                $assignedTo = trim(($disbursement->assignedTo->fname ?? '') . ' ' . ($disbursement->assignedTo->lname ?? '')) ?: ($disbursement->assigned_to ?? 'N/A');
                            } elseif ($disbursement->assigned_to) {
                                $assignedTo = $disbursement->assigned_to;
                            }

                            $method = $disbursement->medium ?? ($disbursement->payment_type ?? 'N/A');

                            // Update narrative so the JE retains metadata for auditing
                            try {
                                $newNarr = ($journalEntry->narrative ?? '') . " | Disbursed by: {$disbBy} | Assigned to: {$assignedTo} | Method: {$method}";
                                $journalEntry->update(['narrative' => $newNarr]);
                            } catch (\Exception $e) {
                                // Non-fatal - log and continue
                                logMsg("Warning: failed to append metadata to disbursement JE {$journalEntry->journal_number}: " . $e->getMessage());
                            }

                            logMsg("Disbursement journal created: {$journalEntry->journal_number} (Disbursed by: {$disbBy}; Method: {$method})");
                            $stats['disbursements_created']++;
                        }
                    } catch (\Exception $e) {
                        logMsg("Error creating disbursement entry: " . $e->getMessage());
                        $stats['errors']++;
                    }
                }
            }
        } else {
            logMsg("No disbursements found for this loan.");
        }

        // Process repayments
        $repayments = Repayment::where('loan_id', $loan->id)
            ->where('amount', '>', 0)
            ->orderBy('date_created')
            ->get();

        if ($repayments->count() > 0) {
            logMsg("Processing {$repayments->count()} repayments...");

            foreach ($repayments as $repayment) {
                // Check if repayment journal already exists
                $repaymentJournal = JournalEntry::where('reference_type', 'Repayment')
                    ->where('reference_id', $repayment->id)
                    ->first();

                if ($repaymentJournal) {
                    $stats['repayments_skipped']++;
                } else {
                    try {
                        logMsg("Creating repayment journal for amount: UGX " . number_format($repayment->amount, 2) . "...");

                        $journalEntry = $accountingService->postRepaymentEntry($repayment, $loan);

                        if ($journalEntry) {
                            // Append repayment metadata: who processed and payment method/platform
                            $procBy = 'N/A';
                            if ($repayment->addedBy) {
                                $procBy = trim(($repayment->addedBy->fname ?? '') . ' ' . ($repayment->addedBy->lname ?? '')) ?: ($repayment->added_by ?? 'N/A');
                            } elseif ($repayment->added_by) {
                                $procBy = $repayment->added_by;
                            }

                            $method = $repayment->platform ?? ($repayment->payment_method ?? ($repayment->payment_phone ? 'mobile' : 'N/A'));

                            try {
                                $newNarr = ($journalEntry->narrative ?? '') . " | Processed by: {$procBy} | Method: {$method}";
                                $journalEntry->update(['narrative' => $newNarr]);
                            } catch (\Exception $e) {
                                logMsg("Warning: failed to append metadata to repayment JE {$journalEntry->journal_number}: " . $e->getMessage());
                            }

                            logMsg("Repayment journal created: {$journalEntry->journal_number} (Processed by: {$procBy}; Method: {$method})");
                            $stats['repayments_created']++;
                        }
                    } catch (\Exception $e) {
                        logMsg("Error creating repayment entry: " . $e->getMessage());
                        $stats['errors']++;
                    }
                }
            }
        } else {
            logMsg("No repayments found for this loan.");
        }

    } catch (\Exception $e) {
        logMsg("Fatal error processing loan {$loan->id}: " . $e->getMessage());
        $stats['errors']++;
        continue;
    }
}

echo "\n\n=================================================\n";
echo "BACKFILL SUMMARY\n";
echo "=================================================\n";
echo str_pad('Total Loans Processed:', 30, ' ', STR_PAD_RIGHT) . str_pad($stats['total_loans'], 6, ' ', STR_PAD_LEFT) . "\n";
echo str_pad('Disbursement Entries Created:', 30, ' ', STR_PAD_RIGHT) . str_pad($stats['disbursements_created'], 6, ' ', STR_PAD_LEFT) . "\n";
echo str_pad('Disbursement Entries Skipped:', 30, ' ', STR_PAD_RIGHT) . str_pad($stats['disbursements_skipped'], 6, ' ', STR_PAD_LEFT) . "\n";
echo str_pad('Repayment Entries Created:', 30, ' ', STR_PAD_RIGHT) . str_pad($stats['repayments_created'], 6, ' ', STR_PAD_LEFT) . "\n";
echo str_pad('Repayment Entries Skipped:', 30, ' ', STR_PAD_RIGHT) . str_pad($stats['repayments_skipped'], 6, ' ', STR_PAD_LEFT) . "\n";
echo str_pad('Errors Encountered:', 30, ' ', STR_PAD_RIGHT) . str_pad($stats['errors'], 6, ' ', STR_PAD_LEFT) . "\n";
echo "=================================================\n\n";

echo "Backfill completed!\n\n";
