<?php

/**
 * Sync Verified Members to Approved Status
 * 
 * This script updates all members who have verified=1 but status='pending'
 * to status='approved'. This fixes the inconsistency between the old verified
 * column and the new status column.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Member;

echo "Starting member status synchronization...\n\n";

// Count members that need updating
$needsUpdate = DB::table('members')
    ->where('verified', 1)
    ->where('status', 'pending')
    ->where('soft_delete', 0)
    ->count();

echo "Found {$needsUpdate} verified members with 'pending' status that need to be updated.\n";

if ($needsUpdate > 0) {
    echo "Updating members...\n";
    
    // Update all verified members to approved status
    $updated = DB::table('members')
        ->where('verified', 1)
        ->where('status', 'pending')
        ->where('soft_delete', 0)
        ->update([
            'status' => 'approved',
            'approved_at' => DB::raw('COALESCE(approved_at, NOW())'), // Only set if null
        ]);
    
    echo "✓ Successfully updated {$updated} members to 'approved' status!\n\n";
    
    // Show summary
    echo "Current statistics:\n";
    echo "- Total Members: " . DB::table('members')->where('soft_delete', 0)->count() . "\n";
    echo "- Approved Members: " . DB::table('members')->where('status', 'approved')->where('soft_delete', 0)->count() . "\n";
    echo "- Pending Members: " . DB::table('members')->where('status', 'pending')->where('soft_delete', 0)->count() . "\n";
    echo "- Verified (verified=1): " . DB::table('members')->where('verified', 1)->where('soft_delete', 0)->count() . "\n";
    
} else {
    echo "✓ All verified members already have 'approved' status. No updates needed.\n";
}

echo "\nDone!\n";
