<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Member;
use App\Models\Fee;

echo "=== SEARCH FOR NORAH MEMBERS ===\n\n";

// Search for all members with Norah in their name
$members = Member::where('fname', 'like', '%Norah%')
    ->orWhere('lname', 'like', '%Norah%')
    ->orWhere('fname', 'like', '%Nakamatte%')
    ->orWhere('lname', 'like', '%Nakamatte%')
    ->get();

echo "Found " . $members->count() . " member(s):\n\n";

foreach ($members as $member) {
    echo "ID: {$member->id}\n";
    echo "Name: {$member->fname} {$member->lname}\n";
    echo "Status: {$member->status}\n";
    echo "Created: " . ($member->date_created ?? 'N/A') . "\n";
    
    // Check fees for this member
    $fees = Fee::where('member_id', $member->id)
        ->where('status', 1)
        ->count();
    
    echo "Paid Fees Count: {$fees}\n";
    
    // Check specifically for mandatory fees
    $mandatoryFees = Fee::where('member_id', $member->id)
        ->where('status', 1)
        ->whereIn('fees_type_id', [1, 5, 6, 7, 8]) // The mandatory fee type IDs
        ->get();
    
    if ($mandatoryFees->count() > 0) {
        echo "Mandatory Fees Paid:\n";
        foreach ($mandatoryFees as $fee) {
            echo "  - Fee Type ID: {$fee->fees_type_id}, Amount: {$fee->amount}\n";
        }
    } else {
        echo "Mandatory Fees Paid: None\n";
    }
    
    echo "---\n\n";
}

// Also check member ID 612 specifically
echo "\n=== MEMBER ID 612 ===\n";
$member612 = Member::find(612);
if ($member612) {
    echo "Name: {$member612->fname} {$member612->lname}\n";
    echo "All fees for this member:\n";
    $allFees = Fee::where('member_id', 612)->get();
    echo "Total fees: " . $allFees->count() . "\n";
    foreach ($allFees as $fee) {
        echo "  Fee Type: {$fee->fees_type_id}, Amount: {$fee->amount}, Status: " . ($fee->status == 1 ? 'Paid' : 'Unpaid') . "\n";
    }
}
