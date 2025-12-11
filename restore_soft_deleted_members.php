<?php

/**
 * PRODUCTION FIX: Restore soft-deleted members who have loans
 * 
 * Issue: Some members are marked as soft_delete=1 but have active loans,
 * making them invisible in the members list.
 * 
 * Specific cases found:
 * - Okwii Michael (ID: 505) - Has loan PLOAN1743223837 (UGX 1,500,000)
 * - Oliver Audo (ID: 618) - Has loan PDLOAN2511151857001 (UGX 5,000)
 * 
 * This script will:
 * 1. Find all soft-deleted members with loans
 * 2. Restore them by setting soft_delete=0
 * 3. Generate a report of restored members
 * 
 * Usage: php restore_soft_deleted_members.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo str_repeat("=", 70) . "\n";
echo "RESTORE SOFT-DELETED MEMBERS WITH LOANS\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";

// Step 1: Find all soft-deleted members
echo "Step 1: Finding soft-deleted members...\n";
$softDeletedMembers = DB::table('members')
    ->where('soft_delete', 1)
    ->select('id', 'code', 'fname', 'lname', 'nin', 'contact', 'status', 'verified', 'branch_id')
    ->get();

echo "Found {$softDeletedMembers->count()} soft-deleted member(s)\n\n";

if ($softDeletedMembers->count() == 0) {
    echo "✅ No soft-deleted members found. Nothing to fix!\n";
    exit(0);
}

// Step 2: Check which ones have loans
echo "Step 2: Checking for members with loans...\n";
$membersToRestore = [];
$membersToKeepDeleted = [];

foreach ($softDeletedMembers as $member) {
    $loans = DB::table('personal_loans')
        ->where('member_id', $member->id)
        ->select('id', 'code', 'principal', 'status', 'verified')
        ->get();
    
    if ($loans->count() > 0) {
        $membersToRestore[] = [
            'member' => $member,
            'loans' => $loans
        ];
    } else {
        $membersToKeepDeleted[] = $member;
    }
}

echo "Members with loans (need restoration): " . count($membersToRestore) . "\n";
echo "Members without loans (keep deleted): " . count($membersToKeepDeleted) . "\n\n";

if (count($membersToRestore) == 0) {
    echo "✅ No soft-deleted members with loans found. Nothing to restore!\n";
    exit(0);
}

// Step 3: Display members to be restored
echo str_repeat("-", 70) . "\n";
echo "MEMBERS TO BE RESTORED:\n";
echo str_repeat("-", 70) . "\n\n";

foreach ($membersToRestore as $data) {
    $member = $data['member'];
    $loans = $data['loans'];
    
    echo "Member ID: {$member->id}\n";
    echo "Name: {$member->fname} {$member->lname}\n";
    echo "Code: {$member->code}\n";
    echo "NIN: {$member->nin}\n";
    echo "Contact: {$member->contact}\n";
    echo "Branch ID: {$member->branch_id}\n";
    echo "Status: {$member->status}, Verified: {$member->verified}\n";
    echo "Loans ({$loans->count()}):\n";
    
    foreach ($loans as $loan) {
        $status = $loan->status == 0 ? 'Active' : 'Complete';
        $verified = $loan->verified == 0 ? 'Not Verified' : ($loan->verified == 1 ? 'Approved' : 'Rejected');
        echo "  - {$loan->code}: UGX " . number_format($loan->principal) . " ({$status}, {$verified})\n";
    }
    echo "\n";
}

// Step 4: Restore members (auto-confirm for production)
echo str_repeat("-", 70) . "\n";
echo "Step 3: Restoring members...\n\n";

$restored = 0;
$failed = 0;

foreach ($membersToRestore as $data) {
    $member = $data['member'];
    
    try {
        $updated = DB::table('members')
            ->where('id', $member->id)
            ->update(['soft_delete' => 0]);
        
        if ($updated) {
            echo "✅ Restored: {$member->fname} {$member->lname} (ID: {$member->id}, Code: {$member->code})\n";
            $restored++;
        } else {
            echo "⚠️  Already restored or not found: {$member->fname} {$member->lname} (ID: {$member->id})\n";
        }
    } catch (\Exception $e) {
        echo "❌ Failed to restore {$member->fname} {$member->lname} (ID: {$member->id}): {$e->getMessage()}\n";
        $failed++;
    }
}

// Step 5: Summary
echo "\n" . str_repeat("=", 70) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 70) . "\n";
echo "Total soft-deleted members found: {$softDeletedMembers->count()}\n";
echo "Members with loans: " . count($membersToRestore) . "\n";
echo "Successfully restored: {$restored}\n";
echo "Failed: {$failed}\n";
echo "\n✅ Members should now be visible in the members list!\n";
echo str_repeat("=", 70) . "\n";

// Step 6: Verify restoration
echo "\nVerifying restoration...\n";
$stillSoftDeleted = DB::table('members')
    ->where('soft_delete', 1)
    ->whereIn('id', collect($membersToRestore)->pluck('member.id')->toArray())
    ->count();

if ($stillSoftDeleted > 0) {
    echo "⚠️  WARNING: {$stillSoftDeleted} member(s) still have soft_delete = 1\n";
} else {
    echo "✅ All members successfully verified as restored!\n";
}

echo "\n=== DONE ===\n";
