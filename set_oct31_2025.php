<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Disbursement;
use App\Models\Repayment;
use App\Models\JournalEntry;
use Carbon\Carbon;

$csvIn = __DIR__ . '/storage/future_date_corrections.csv';
$csvOut = __DIR__ . '/storage/future_date_finalized.csv';
$log = __DIR__ . '/storage/logs/set_oct31_2025.log';

if (!file_exists($csvIn)) {
    echo "Input CSV not found: {$csvIn}\n";
    exit(1);
}

$target = '2025-10-31';
file_put_contents($log, "\n---- Run at " . date('Y-m-d H:i:s') . " ----\n", FILE_APPEND);

$hin = fopen($csvIn, 'r');
$header = fgetcsv($hin);

$hout = fopen($csvOut, 'w');
fputcsv($hout, array_merge($header, ['final_date','je_before','je_after']));

$count = 0;
while (($row = fgetcsv($hin)) !== false) {
    $data = array_combine($header, $row);
    $type = $data['type'];
    $id = $data['id'];
    $old = $data['old_date'];
    $prevNew = $data['new_date'];

    if ($type === 'disbursement') {
        $d = Disbursement::find($id);
        if (!$d) {
            file_put_contents($log, "Disbursement {$id} not found\n", FILE_APPEND);
            continue;
        }
        $d->date_approved = $target;
        $d->save();

        $je = JournalEntry::where('reference_type', 'Disbursement')->where('reference_id', $d->id)->first();
        $jeBefore = $je ? (string)$je->transaction_date : '';
        if ($je) {
            $je->transaction_date = $target;
            $je->save();
        }

        fputcsv($hout, array_merge($data, [$target, $jeBefore, ($je ? (string)$je->transaction_date : '')]));
        file_put_contents($log, "Set Disbursement {$id} and JE " . ($je? $je->journal_number : 'none') . " to {$target}\n", FILE_APPEND);
        $count++;

    } else {
        // repayment
        $r = Repayment::find($id);
        if (!$r) {
            file_put_contents($log, "Repayment {$id} not found\n", FILE_APPEND);
            continue;
        }
        $r->date_created = $target;
        $r->save();

        $je = JournalEntry::where('reference_type', 'Repayment')->where('reference_id', $r->id)->first();
        $jeBefore = $je ? (string)$je->transaction_date : '';
        if ($je) {
            $je->transaction_date = $target;
            $je->save();
        }

        fputcsv($hout, array_merge($data, [$target, $jeBefore, ($je ? (string)$je->transaction_date : '')]));
        file_put_contents($log, "Set Repayment {$id} and JE " . ($je? $je->journal_number : 'none') . " to {$target}\n", FILE_APPEND);
        $count++;
    }
}

fclose($hin);
fclose($hout);

echo "Applied final date {$target} to {$count} rows. Output CSV: storage/future_date_finalized.csv. Log: storage/logs/set_oct31_2025.log\n";
exit(0);

?>
