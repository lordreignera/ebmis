<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;

echo "Finding members with loan assessment data...\n";
echo str_repeat("=", 80) . "\n\n";

$members = Member::whereHas('businesses')
    ->orWhereHas('assets')
    ->orWhereHas('liabilities')
    ->with(['businesses', 'assets', 'liabilities'])
    ->limit(5)
    ->get();

if ($members->count() > 0) {
    foreach ($members as $member) {
        echo "Member ID: " . $member->id . "\n";
        echo "Name: " . $member->fname . " " . $member->lname . "\n";
        echo "Businesses: " . $member->businesses->count() . "\n";
        echo "Assets: " . $member->assets->count() . " (Total Value: UGX " . number_format($member->total_assets, 0) . ")\n";
        echo "Liabilities: " . $member->liabilities->count() . " (Total Value: UGX " . number_format($member->total_liabilities, 0) . ")\n";
        echo "Net Worth: UGX " . number_format($member->net_worth, 0) . "\n";
        echo "\nView at: http://localhost:84/admin/members/" . $member->id . "\n";
        echo str_repeat("-", 80) . "\n\n";
    }
} else {
    echo "No members found with assessment data\n";
}
