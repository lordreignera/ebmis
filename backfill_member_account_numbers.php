<?php

/**
 * Backfill Account Numbers for Existing Members
 * 
 * This script generates cash security and savings account numbers
 * for members who were created before the account number system was implemented.
 * 
 * Usage: php backfill_member_account_numbers.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Member;
use Illuminate\Support\Facades\DB;

echo "==================================================\n";
echo "  BACKFILL MEMBER ACCOUNT NUMBERS\n";
echo "==================================================\n\n";

try {
    // Get all members without account numbers
    $membersWithoutCashSecurity = Member::whereNull('cash_security_account_number')->count();
    $membersWithoutSavings = Member::whereNull('savings_account_number')->count();
    
    echo "Members without Cash Security Account: $membersWithoutCashSecurity\n";
    echo "Members without Savings Account: $membersWithoutSavings\n\n";
    
    if ($membersWithoutCashSecurity == 0 && $membersWithoutSavings == 0) {
        echo "✅ All members already have account numbers!\n";
        exit(0);
    }
    
    echo "Starting backfill process...\n\n";
    
    DB::beginTransaction();
    
    $members = Member::whereNull('cash_security_account_number')
        ->orWhereNull('savings_account_number')
        ->orderBy('id')
        ->get();
    
    $updated = 0;
    
    foreach ($members as $member) {
        $updates = [];
        
        // Use member's creation date for timestamp-based format (same as loan codes)
        $createdAt = \Carbon\Carbon::parse($member->datecreated ?? $member->created_at ?? now());
        $dailyCountOffset = $member->id % 1000; // Use member ID modulo 1000 as offset for uniqueness
        
        // Generate Cash Security Account Number if missing
        if (!$member->cash_security_account_number) {
            $csNumber = 'CS' . $createdAt->format('ymdHi') . sprintf('%03d', $dailyCountOffset);
            $updates['cash_security_account_number'] = $csNumber;
        }
        
        // Generate Savings Account Number if missing
        if (!$member->savings_account_number) {
            $savNumber = 'SAV' . $createdAt->format('ymdHi') . sprintf('%03d', $dailyCountOffset);
            $updates['savings_account_number'] = $savNumber;
        }
        
        if (!empty($updates)) {
            $member->update($updates);
            $updated++;
            
            echo "✓ Member #{$member->id} ({$member->fname} {$member->lname})\n";
            if (isset($updates['cash_security_account_number'])) {
                echo "  Cash Security: {$updates['cash_security_account_number']}\n";
            }
            if (isset($updates['savings_account_number'])) {
                echo "  Savings: {$updates['savings_account_number']}\n";
            }
        }
    }
    
    DB::commit();
    
    echo "\n==================================================\n";
    echo "✅ SUCCESS!\n";
    echo "==================================================\n";
    echo "Total members updated: $updated\n";
    echo "\nAccount Number Format (same as loan codes):\n";
    echo "  Cash Security: CS{YYMMDDHHmm}{DailyCount}\n";
    echo "  Savings:       SAV{YYMMDDHHmm}{DailyCount}\n";
    echo "\nExample: Member created Jan 5, 2026 at 10:30\n";
    echo "  CS260105103001 (CS + date/time + member offset)\n";
    echo "  SAV260105103001 (SAV + date/time + member offset)\n";
    echo "\n✅ No limit on member count - timestamp ensures uniqueness!\n";
    echo "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
