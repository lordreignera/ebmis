<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$member = DB::table('members')->where('id', 619)->first();

if ($member) {
    echo "Member ID: {$member->id}\n";
    echo "Name: {$member->fname} {$member->lname}\n";
    echo "Member Type Value: {$member->member_type}\n";
    echo "Member Type Display: " . ($member->member_type == 2 ? 'Individual' : 'Group Member') . "\n";
} else {
    echo "Member not found\n";
}
