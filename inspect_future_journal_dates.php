<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\JournalEntry;
use App\Models\Disbursement;
use App\Models\Repayment;

echo "Journal entries with transaction_date > today:\n\n";
$today = date('Y-m-d');
$entries = JournalEntry::where('transaction_date', '>', $today)
    ->orderBy('transaction_date')
    ->limit(50)
    ->get();

if ($entries->isEmpty()) {
    echo "No future-dated journal entries found.\n";
    exit(0);
}

foreach ($entries as $e) {
    $refInfo = '';
    if ($e->reference_type === 'Disbursement') {
        $d = Disbursement::find($e->reference_id);
        $refInfo = $d ? ('disbursement.date_approved=' . ($d->date_approved ? $d->date_approved->format('Y-m-d') : 'NULL') . ', created_at=' . ($d->created_at ? $d->created_at->format('Y-m-d') : 'NULL')) : 'disbursement not found';
    } elseif ($e->reference_type === 'Repayment') {
        $r = Repayment::find($e->reference_id);
        $refInfo = $r ? ('repayment.date_created=' . ($r->date_created ? (is_string($r->date_created)? $r->date_created : $r->date_created->format('Y-m-d')) : 'NULL')) : 'repayment not found';
    }

    printf("%s | %s | %s | %s | %s\n", $e->journal_number, $e->transaction_date->format('Y-m-d'), $e->reference_type, $e->reference_id, $refInfo);
}

echo "\nDone.\n";
