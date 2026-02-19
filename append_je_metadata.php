<?php

/**
 * Append missing disbursement/repayment metadata to existing Journal Entries
 * - For Disbursement JEs: append "Disbursed by", "Assigned to", "Method"
 * - For Repayment JEs: append "Processed by", "Method"
 * Defaults to 'system/sysadmin' when user info is missing.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JournalEntry;
use App\Models\Disbursement;
use App\Models\Repayment;

// log file
$logFile = __DIR__ . '/storage/logs/append_je_metadata.log';
if (!is_dir(dirname($logFile))) {@mkdir(dirname($logFile), 0755, true);}

function logMsgAppend($m) {
    global $logFile;
    $t = date('Y-m-d H:i:s');
    $line = "[{$t}] " . $m . "\n";
    echo $line;
    @file_put_contents($logFile, $line, FILE_APPEND);
}

$stats = [
    'disb_updated' => 0,
    'disb_skipped' => 0,
    'repay_updated' => 0,
    'repay_skipped' => 0,
    'errors' => 0,
];

logMsgAppend("Starting append_je_metadata run...");

// Process Disbursement journal entries
$disbJEs = JournalEntry::where('reference_type', 'Disbursement')->get();
logMsgAppend("Found " . $disbJEs->count() . " Disbursement journal entries to inspect.");

foreach ($disbJEs as $je) {
    try {
        $contains = stripos($je->narrative ?? '', 'Disbursed by:') !== false;
        if ($contains) { $stats['disb_skipped']++; continue; }

        $disb = Disbursement::find($je->reference_id);
        $disbBy = 'system/sysadmin';
        $assigned = 'system/sysadmin';
        $method = 'N/A';

        if ($disb) {
            if ($disb->addedBy) {
                $disbBy = trim(($disb->addedBy->fname ?? '') . ' ' . ($disb->addedBy->lname ?? '')) ?: ($disb->added_by ?? $disbBy);
            } elseif ($disb->added_by) {
                $disbBy = $disb->added_by;
            }

            if ($disb->assignedTo) {
                $assigned = trim(($disb->assignedTo->fname ?? '') . ' ' . ($disb->assignedTo->lname ?? '')) ?: ($disb->assigned_to ?? $assigned);
            } elseif ($disb->assigned_to) {
                $assigned = $disb->assigned_to;
            }

            $method = $disb->medium ?? ($disb->payment_type ?? $method);
        }

        $newNarr = ($je->narrative ?? '') . " | Disbursed by: {$disbBy} | Assigned to: {$assigned} | Method: {$method}";
        $je->update(['narrative' => $newNarr]);
        $stats['disb_updated']++;
        logMsgAppend("Updated JE {$je->journal_number} (Disbursed by: {$disbBy}; Method: {$method})");
    } catch (\Exception $e) {
        $stats['errors']++;
        logMsgAppend("Error updating JE Id {$je->Id}: " . $e->getMessage());
    }
}

// Process Repayment journal entries
$repJEs = JournalEntry::where('reference_type', 'Repayment')->get();
logMsgAppend("Found " . $repJEs->count() . " Repayment journal entries to inspect.");

foreach ($repJEs as $je) {
    try {
        $contains = stripos($je->narrative ?? '', 'Processed by:') !== false;
        if ($contains) { $stats['repay_skipped']++; continue; }

        $rep = Repayment::find($je->reference_id);
        $procBy = 'system/sysadmin';
        $method = 'N/A';

        if ($rep) {
            if ($rep->addedBy) {
                $procBy = trim(($rep->addedBy->fname ?? '') . ' ' . ($rep->addedBy->lname ?? '')) ?: ($rep->added_by ?? $procBy);
            } elseif ($rep->added_by) {
                $procBy = $rep->added_by;
            }

            $method = $rep->platform ?? ($rep->payment_method ?? ($rep->payment_phone ? 'mobile' : $method));
        }

        $newNarr = ($je->narrative ?? '') . " | Processed by: {$procBy} | Method: {$method}";
        $je->update(['narrative' => $newNarr]);
        $stats['repay_updated']++;
        logMsgAppend("Updated JE {$je->journal_number} (Processed by: {$procBy}; Method: {$method})");
    } catch (\Exception $e) {
        $stats['errors']++;
        logMsgAppend("Error updating JE Id {$je->Id}: " . $e->getMessage());
    }
}

logMsgAppend("\nSummary: Disbursements updated: {$stats['disb_updated']}, skipped: {$stats['disb_skipped']}; Repayments updated: {$stats['repay_updated']}, skipped: {$stats['repay_skipped']}; errors: {$stats['errors']}");
logMsgAppend("append_je_metadata run completed.");

// End
