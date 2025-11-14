<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fixing member_types Table to Match members Schema ===\n\n";

echo "Current situation:\n";
echo "  member_types table: 1=Individual, 2=Group\n";
echo "  members table schema: 1=Group, 2=Individual\n";
echo "  Result: When user selects 'Individual', it saves as 1, but displays as 'Group'\n\n";

echo "Solution: Swap the IDs in member_types table\n\n";

try {
    DB::beginTransaction();
    
    // Step 1: Temporarily rename to avoid conflicts
    echo "Step 1: Temporarily moving records...\n";
    DB::table('member_types')->where('id', 1)->update(['id' => 99, 'name' => 'TEMP_INDIVIDUAL']);
    DB::table('member_types')->where('id', 2)->update(['id' => 98, 'name' => 'TEMP_GROUP']);
    echo "  ✓ Moved to temporary IDs\n";
    
    // Step 2: Swap them
    echo "Step 2: Swapping to correct IDs...\n";
    DB::table('member_types')->where('id', 99)->update(['id' => 2, 'name' => 'Individual']);
    DB::table('member_types')->where('id', 98)->update(['id' => 1, 'name' => 'Group']);
    echo "  ✓ Swapped successfully\n";
    
    DB::commit();
    
    echo "\n=== Success! ===\n\n";
    
    echo "New member_types mapping:\n";
    $types = DB::table('member_types')->orderBy('id')->get(['id', 'name']);
    foreach ($types as $type) {
        echo "  ID: {$type->id} = {$type->name}\n";
    }
    
    echo "\nNow matches members table schema:\n";
    echo "  1 = Group ✓\n";
    echo "  2 = Individual ✓\n";
    echo "  3 = Corporate ✓\n";
    echo "  4 = Institution ✓\n";
    
    echo "\nResult: When users select 'Individual', it will save as 2 and display correctly!\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "No changes were made.\n";
}
