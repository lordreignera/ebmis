<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\MemberType;
use Illuminate\Support\Facades\DB;

echo "=== Checking MemberType Table ===\n\n";

// Check all member types
$memberTypes = MemberType::orderBy('id')->get();

echo "Member Types in database:\n";
foreach ($memberTypes as $type) {
    echo "  ID: {$type->id} - Name: {$type->name} - Status: {$type->status}\n";
}

echo "\n=== Checking member_types table structure ===\n";
$columns = DB::select("SHOW COLUMNS FROM member_types");
foreach ($columns as $col) {
    echo "  {$col->Field} ({$col->Type}) - Null: {$col->Null} - Default: {$col->Default}\n";
}

echo "\n=== Testing form dropdown data ===\n";
$activeTypes = MemberType::active()->get();
echo "Active member types (what appears in the form):\n";
foreach ($activeTypes as $type) {
    echo "  <option value=\"{$type->id}\">{$type->name}</option>\n";
}

echo "\n=== Verifying the correct mapping ===\n";
echo "According to database schema:\n";
echo "  1 = Group\n";
echo "  2 = Individual (non-group)\n";
echo "  3 = Corporate\n";
echo "  4 = Field user\n\n";

echo "What's in member_types table:\n";
foreach ($memberTypes as $type) {
    echo "  ID {$type->id} = {$type->name}\n";
}

echo "\n=== Complete ===\n";
