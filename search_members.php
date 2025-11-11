<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;

echo "Searching for members with 'Norah' or 'Nakamatte'...\n\n";

$members = Member::where('fname', 'LIKE', '%Norah%')
                ->orWhere('lname', 'LIKE', '%Norah%')
                ->orWhere('fname', 'LIKE', '%Nakamatte%')
                ->orWhere('lname', 'LIKE', '%Nakamatte%')
                ->get();

if ($members->count() > 0) {
    foreach ($members as $member) {
        echo "ID: " . $member->id . "\n";
        echo "Name: " . $member->fname . " " . $member->lname . "\n";
        echo "Status: " . $member->status . "\n";
        echo "Contact: " . $member->contact . "\n";
        echo "---\n";
    }
} else {
    echo "No members found\n";
    echo "\nSearching for ANY members (first 10)...\n\n";
    
    $anyMembers = Member::take(10)->get();
    foreach ($anyMembers as $member) {
        echo "ID: " . $member->id . " - " . $member->fname . " " . $member->lname . "\n";
    }
}
