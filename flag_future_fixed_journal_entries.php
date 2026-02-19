<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JournalEntry;

$csv = __DIR__ . '/storage/future_date_corrections.csv';
$log = __DIR__ . '/storage/logs/flag_future_fixed_journal_entries.log';

if (!file_exists($csv)) {
    echo "CSV file not found: {$csv}\n";
    exit(1);
}

$h = fopen($csv, 'r');
$header = fgetcsv($h);
$count = 0;
file_put_contents($log, "\n---- Run at " . date('Y-m-d H:i:s') . " ----\n", FILE_APPEND);

while (($row = fgetcsv($h)) !== false) {
    $data = array_combine($header, $row);
    $type = $data['type'];
    $id = $data['id'];

    $refType = $type === 'disbursement' ? 'Disbursement' : 'Repayment';

    $je = JournalEntry::where('reference_type', $refType)->where('reference_id', $id)->first();
    if (!$je) {
        file_put_contents($log, "No JE found for {$refType} {$id}\n", FILE_APPEND);
        continue;
    }

    $flag = 'FLAGGED_FOR_AUDIT:future_date_fix';
    if (strpos($je->narrative ?? '', $flag) === false) {
        $newNarr = trim(($je->narrative ?? '') . ' | ' . $flag, " |\t\n");
        $je->narrative = $newNarr;
        $je->save();
        file_put_contents($log, "Flagged JE {$je->journal_number} (ref {$refType} {$id})\n", FILE_APPEND);
        $count++;
    } else {
        file_put_contents($log, "Already flagged JE {$je->journal_number}\n", FILE_APPEND);
    }
}

fclose($h);

echo "Flagged {$count} journal entries. Log: {$log}\n";
exit(0);

?>
