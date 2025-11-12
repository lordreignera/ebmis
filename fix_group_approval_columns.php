<?php
/**
 * Fix missing approval columns in group_loans table
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking group_loans table columns...\n\n";

// Get current columns
$columns = collect(DB::select('DESCRIBE group_loans'))->pluck('Field')->toArray();

echo "Current columns:\n";
foreach ($columns as $col) {
    echo "  - {$col}\n";
}

echo "\n----------------------------------------\n";
echo "Adding missing approval columns...\n";
echo "----------------------------------------\n\n";

// Check and add each column
$columnsToAdd = [
    'approved_by' => "ALTER TABLE group_loans ADD COLUMN approved_by INT NULL AFTER added_by",
    'date_approved' => "ALTER TABLE group_loans ADD COLUMN date_approved DATETIME NULL AFTER approved_by",
    'rejected_by' => "ALTER TABLE group_loans ADD COLUMN rejected_by INT NULL AFTER date_approved",
    'date_rejected' => "ALTER TABLE group_loans ADD COLUMN date_rejected DATETIME NULL AFTER rejected_by"
];

foreach ($columnsToAdd as $columnName => $sql) {
    if (in_array($columnName, $columns)) {
        echo "✓ Column '{$columnName}' already exists\n";
    } else {
        try {
            DB::statement($sql);
            echo "✓ Successfully added column '{$columnName}'\n";
        } catch (\Exception $e) {
            echo "✗ Failed to add column '{$columnName}': " . $e->getMessage() . "\n";
        }
    }
}

echo "\n----------------------------------------\n";
echo "Verification\n";
echo "----------------------------------------\n\n";

// Verify all columns now exist
$columnsAfter = collect(DB::select('DESCRIBE group_loans'))->pluck('Field')->toArray();

$requiredColumns = ['approved_by', 'date_approved', 'rejected_by', 'date_rejected'];
$allExist = true;

foreach ($requiredColumns as $col) {
    $exists = in_array($col, $columnsAfter);
    $icon = $exists ? '✓' : '✗';
    echo "{$icon} {$col}: " . ($exists ? 'EXISTS' : 'MISSING') . "\n";
    if (!$exists) $allExist = false;
}

echo "\n----------------------------------------\n";
if ($allExist) {
    echo "✅ All approval columns are now present in group_loans!\n";
} else {
    echo "⚠ Some columns are still missing. Please check errors above.\n";
}
echo "----------------------------------------\n\n";
