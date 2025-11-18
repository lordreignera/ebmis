<?php
/**
 * Web-accessible script to fix member types on production
 * 
 * SECURITY: Delete this file after running!
 * 
 * Access via: https://yourdomain.com/fix_member_types_online.php?confirm=yes
 */

// Prevent unauthorized access
$secret_key = 'CHANGE_THIS_SECRET_KEY_12345'; // CHANGE THIS!

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    die('Unauthorized access. Add ?key=YOUR_SECRET_KEY to URL');
}

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Member;
use Illuminate\Support\Facades\DB;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Member Types</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #ce9178; }
        .info { color: #569cd6; }
        pre { background: #2d2d2d; padding: 15px; border-radius: 5px; }
        button { padding: 10px 20px; font-size: 16px; cursor: pointer; background: #007acc; color: white; border: none; border-radius: 5px; }
        button:hover { background: #005a9e; }
    </style>
</head>
<body>
    <h1>🔧 Fix Member Types - Production</h1>
    
<?php

echo "<pre>\n";
echo "=== FIX HISTORICAL MEMBER_TYPE DATA ===\n\n";

echo "<span class='warning'>PROBLEM:</span>\n";
echo "- Historical data uses: member_type=2 for Individual, member_type=1 for Group\n";
echo "- member_types table says: ID=1 is Individual, ID=2 is Group\n";
echo "- This causes inconsistency with new members\n\n";

echo "<span class='info'>SOLUTION:</span>\n";
echo "Update ALL old members to use correct member_type values:\n";
echo "- Members WITHOUT group_id (or group_id=0) → member_type = 1 (Individual)\n";
echo "- Members WITH valid group_id → member_type = 2 (Group)\n\n";

// Show current state
try {
    $type1_with_group = Member::where('member_type', 1)
        ->whereNotNull('group_id')
        ->where('group_id', '>', 0)
        ->count();
        
    $type1_without_group = Member::where('member_type', 1)
        ->where(function($q) {
            $q->whereNull('group_id')->orWhere('group_id', 0);
        })
        ->count();
        
    $type2_with_group = Member::where('member_type', 2)
        ->whereNotNull('group_id')
        ->where('group_id', '>', 0)
        ->count();
        
    $type2_without_group = Member::where('member_type', 2)
        ->where(function($q) {
            $q->whereNull('group_id')->orWhere('group_id', 0);
        })
        ->count();

    echo "<span class='info'>CURRENT STATE:</span>\n";
    echo "- member_type=1 WITH group: {$type1_with_group}\n";
    echo "- member_type=1 WITHOUT group: {$type1_without_group}\n";
    echo "- member_type=2 WITH group: {$type2_with_group}\n";
    echo "- member_type=2 WITHOUT group: {$type2_without_group}\n\n";

    // Check if already fixed
    if ($type1_with_group === 0 && $type2_without_group === 0) {
        echo "<span class='success'>✓ Member types are already correct! No changes needed.</span>\n\n";
        echo "</pre>";
        echo "<p><a href='?'>Refresh</a></p>";
        echo "</body></html>";
        exit;
    }

    echo "<span class='warning'>WILL UPDATE:</span>\n";
    echo "- {$type2_without_group} members (type=2, no group) → type=1 (Individual)\n";
    echo "- {$type1_with_group} members (type=1, has group) → type=2 (Group)\n\n";

    // Check for confirmation
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
        echo "</pre>";
        echo "<p><span class='warning'>⚠️  Ready to update {$type1_with_group} + {$type2_without_group} = " . ($type1_with_group + $type2_without_group) . " members</span></p>";
        echo "<form method='get'>";
        echo "<input type='hidden' name='key' value='{$secret_key}'>";
        echo "<input type='hidden' name='confirm' value='yes'>";
        echo "<button type='submit'>🚀 RUN THE FIX NOW</button>";
        echo "</form>";
        echo "<p><small>This will use a database transaction. Safe to run multiple times.</small></p>";
        echo "</body></html>";
        exit;
    }

    // Run the fix
    echo "<span class='info'>🚀 EXECUTING FIX...</span>\n\n";
    
    DB::beginTransaction();

    try {
        // Fix members WITHOUT group (should be Individual = type 1)
        $updated1 = DB::table('members')
            ->where('member_type', 2)
            ->where(function($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            ->update(['member_type' => 1]);
        
        echo "<span class='success'>✓ Updated {$updated1} members without groups to Individual (type=1)</span>\n";
        
        // Fix members WITH group (should be Group = type 2)
        $updated2 = DB::table('members')
            ->where('member_type', 1)
            ->whereNotNull('group_id')
            ->where('group_id', '>', 0)
            ->update(['member_type' => 2]);
        
        echo "<span class='success'>✓ Updated {$updated2} members with groups to Group (type=2)</span>\n";
        
        DB::commit();
        
        echo "\n<span class='success'>✓✓✓ SUCCESS! ✓✓✓</span>\n\n";
        
        // Show new state
        $new_type1_with_group = Member::where('member_type', 1)
            ->whereNotNull('group_id')
            ->where('group_id', '>', 0)
            ->count();
            
        $new_type1_without_group = Member::where('member_type', 1)
            ->where(function($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            ->count();
            
        $new_type2_with_group = Member::where('member_type', 2)
            ->whereNotNull('group_id')
            ->where('group_id', '>', 0)
            ->count();
            
        $new_type2_without_group = Member::where('member_type', 2)
            ->where(function($q) {
                $q->whereNull('group_id')->orWhere('group_id', 0);
            })
            ->count();
        
        echo "<span class='info'>NEW STATE:</span>\n";
        echo "- member_type=1 WITH group: {$new_type1_with_group} <span class='success'>(should be 0 ✓)</span>\n";
        echo "- member_type=1 WITHOUT group: {$new_type1_without_group} <span class='success'>(Individual members ✓)</span>\n";
        echo "- member_type=2 WITH group: {$new_type2_with_group} <span class='success'>(Group members ✓)</span>\n";
        echo "- member_type=2 WITHOUT group: {$new_type2_without_group} <span class='success'>(should be 0 ✓)</span>\n\n";
        
        echo "<span class='warning'>⚠️  IMPORTANT: Delete this file now for security!</span>\n";
        echo "File to delete: " . __FILE__ . "\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "<span class='error'>✗ ERROR: " . $e->getMessage() . "</span>\n\n";
        echo "<span class='error'>Transaction rolled back. Database unchanged.</span>\n";
    }

} catch (\Exception $e) {
    echo "<span class='error'>✗ FATAL ERROR: " . $e->getMessage() . "</span>\n";
}

echo "</pre>";
echo "<p><a href='?key={$secret_key}'>Refresh / Check Status</a></p>";
?>

<hr>
<p><small>Generated: <?php echo date('Y-m-d H:i:s'); ?></small></p>

</body>
</html>
