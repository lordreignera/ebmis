<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking which documents have actual files...\n\n";

$documents = DB::table('member_documents')->get();
$found = 0;
$notFound = 0;
$copied = 0;

foreach ($documents as $doc) {
    $filePath = $doc->file_path;
    
    // Check multiple possible locations
    $locations = [
        base_path('bimsadmin/public/' . $filePath),
        base_path('bimsadmin/writable/' . $filePath),
        public_path($filePath),
    ];
    
    $fileExists = false;
    $sourceFile = null;
    
    foreach ($locations as $location) {
        if (file_exists($location)) {
            $fileExists = true;
            $sourceFile = $location;
            break;
        }
    }
    
    if ($fileExists) {
        $found++;
        echo "✓ Found: {$doc->document_name} => {$sourceFile}\n";
        
        // Copy to public/uploads if not already there
        $targetPath = public_path($filePath);
        $targetDir = dirname($targetPath);
        
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        if (!file_exists($targetPath)) {
            copy($sourceFile, $targetPath);
            $copied++;
            echo "  → Copied to: {$targetPath}\n";
            
            // Update file size in database
            $fileSize = filesize($targetPath);
            DB::table('member_documents')
                ->where('id', $doc->id)
                ->update(['file_size' => $fileSize]);
        }
    } else {
        $notFound++;
        echo "✗ Missing: {$doc->document_name} ({$filePath})\n";
    }
}

echo "\n========================================\n";
echo "Summary:\n";
echo "========================================\n";
echo "Total documents in database: " . count($documents) . "\n";
echo "Files found: {$found}\n";
echo "Files copied: {$copied}\n";
echo "Files missing: {$notFound}\n";
echo "========================================\n";
