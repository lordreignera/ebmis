<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Updating member 619 from Group Member to Individual...\n";

$updated = DB::table('members')
    ->where('id', 619)
    ->update(['member_type' => 2]);

if ($updated) {
    echo "✓ Successfully updated member 619 to Individual (member_type = 2)\n";
    
    $member = DB::table('members')->where('id', 619)->first();
    echo "\nVerification:\n";
    echo "Member: {$member->fname} {$member->lname}\n";
    echo "Member Type: {$member->member_type} (" . ($member->member_type == 2 ? 'Individual' : 'Group Member') . ")\n";
} else {
    echo "✗ Failed to update member\n";
}
