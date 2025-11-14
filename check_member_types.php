<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;
use Illuminate\Support\Facades\DB;

echo "=== Checking Member Types Configuration ===\n\n";

// Check the three members from the screenshot
$memberIds = [571, 540, 58];

echo "Checking members: " . implode(', ', $memberIds) . "\n\n";

foreach ($memberIds as $memberId) {
    $member = Member::find($memberId);
    if ($member) {
        echo "Member ID: {$member->id}\n";
        echo "  Code: {$member->code}\n";
        echo "  Name: {$member->fname} {$member->mname} {$member->lname}\n";
        echo "  member_type (DB value): {$member->member_type}\n";
        echo "  member_type (raw): " . var_export($member->getRawOriginal('member_type'), true) . "\n";
        echo "  Contact: {$member->contact}\n";
        echo "  Status: {$member->status}\n";
        echo "\n";
    }
}

echo "\n=== Checking all possible member_type values ===\n";
$types = DB::table('members')->select('member_type', DB::raw('count(*) as count'))
    ->groupBy('member_type')
    ->orderBy('member_type')
    ->get();

foreach ($types as $type) {
    echo "member_type: " . var_export($type->member_type, true) . " - Count: {$type->count}\n";
}

echo "\n=== Checking Member model for member_type logic ===\n";
echo "Looking for accessor/mutator or casts...\n\n";

// Check if there's a members table column definition
$columns = DB::select("SHOW COLUMNS FROM members WHERE Field = 'member_type'");
if ($columns) {
    echo "Database column 'member_type':\n";
    foreach ($columns as $col) {
        echo "  Type: {$col->Type}\n";
        echo "  Null: {$col->Null}\n";
        echo "  Default: {$col->Default}\n";
    }
}

echo "\n=== Checking if member_type should be 1 (individual) or 2 (group) ===\n";
$individual = Member::where('member_type', 1)->count();
$group = Member::where('member_type', 2)->count();
$other = Member::whereNotIn('member_type', [1, 2])->count();

echo "member_type = 1: {$individual} members\n";
echo "member_type = 2: {$group} members\n";
echo "Other values: {$other} members\n";

echo "\n=== Complete ===\n";
