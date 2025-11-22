<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "ANALYZING OLD SYSTEM LOANS VS NEW SYSTEM LOANS\n";
echo str_repeat("=", 80) . "\n\n";

// Isaac's loan (old system)
$oldLoan = DB::table('personal_loans')->where('id', 133)->first();

// Norah's loan (new system)
$newLoan = DB::table('personal_loans')->where('id', 127)->first();

echo "OLD SYSTEM LOAN (Isaac - ID 133):\n";
foreach ($oldLoan as $key => $value) {
    if (in_array($key, ['id', 'code', 'member_id', 'datecreated', 'principal', 'interest', 'period', 'status', 'product_type', 'charge_type'])) {
        echo "  {$key}: " . ($value ?? 'NULL') . "\n";
    }
}

echo "\n";
echo "NEW SYSTEM LOAN (Norah - ID 127):\n";
foreach ($newLoan as $key => $value) {
    if (in_array($key, ['id', 'code', 'member_id', 'datecreated', 'principal', 'interest', 'period', 'status', 'product_type', 'charge_type'])) {
        echo "  {$key}: " . ($value ?? 'NULL') . "\n";
    }
}

echo "\n";
echo str_repeat("-", 80) . "\n";
echo "DIFFERENCES:\n";
echo str_repeat("-", 80) . "\n\n";

foreach ($oldLoan as $key => $value) {
    if (isset($newLoan->$key) && $oldLoan->$key !== $newLoan->$key && in_array($key, ['product_type', 'charge_type', 'status'])) {
        echo "{$key}:\n";
        echo "  Old system: " . ($oldLoan->$key ?? 'NULL') . "\n";
        echo "  New system: " . ($newLoan->$key ?? 'NULL') . "\n\n";
    }
}

// Check schedules structure
echo "OLD SYSTEM SCHEDULES (Loan 133):\n";
$oldSchedules = DB::table('loan_schedules')->where('loan_id', 133)->first();
if ($oldSchedules) {
    foreach ($oldSchedules as $key => $value) {
        echo "  {$key}: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "  No schedules found\n";
}

echo "\n";
echo "NEW SYSTEM SCHEDULES (Loan 127):\n";
$newSchedules = DB::table('loan_schedules')->where('loan_id', 127)->first();
if ($newSchedules) {
    foreach ($newSchedules as $key => $value) {
        echo "  {$key}: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "  No schedules found\n";
}

echo "\n";
