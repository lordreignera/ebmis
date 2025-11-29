<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;

echo "Finding members with BUSINESS profiles...\n";
echo str_repeat("=", 80) . "\n\n";

$members = Member::whereHas('businesses')
    ->with(['businesses.businessType', 'businesses.address'])
    ->limit(3)
    ->get();

if ($members->count() > 0) {
    foreach ($members as $member) {
        echo "Member ID: " . $member->id . "\n";
        echo "Name: " . $member->fname . " " . $member->lname . "\n";
        echo "Businesses: " . $member->businesses->count() . "\n";
        
        foreach ($member->businesses as $business) {
            echo "  - Business Name: " . $business->name . "\n";
            echo "    Type: " . ($business->businessType->name ?? 'N/A') . "\n";
            echo "    Reg No: " . ($business->reg_no ?? 'N/A') . "\n";
            echo "    TIN: " . ($business->tin ?? 'N/A') . "\n";
            if ($business->address) {
                echo "    Address: " . $business->address->full_address . "\n";
            }
        }
        
        echo "\n👉 View at: http://localhost:84/admin/members/" . $member->id . "\n";
        echo str_repeat("-", 80) . "\n\n";
    }
} else {
    echo "No members found with business data\n";
}
