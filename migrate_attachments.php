<?php
/**
 * Migrate old attachments table to new member_documents table
 * Run this script once to migrate all old member attachments
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Starting migration of attachments to member_documents...\n\n";

// Get all attachments with status = 1 (active)
$attachments = DB::table('attachments')
    ->where('status', 1)
    ->orderBy('member_id')
    ->orderBy('date_created')
    ->get();

echo "Found " . count($attachments) . " attachments to migrate.\n\n";

$migrated = 0;
$skipped = 0;
$errors = 0;

foreach ($attachments as $attachment) {
    try {
        // Check if already migrated
        $exists = DB::table('member_documents')
            ->where('member_id', $attachment->member_id)
            ->where('document_name', $attachment->title)
            ->where('file_path', $attachment->url)
            ->exists();

        if ($exists) {
            echo "Skipping (already exists): {$attachment->title} for member {$attachment->member_id}\n";
            $skipped++;
            continue;
        }

        // Determine file type from URL
        $fileExtension = strtolower(pathinfo($attachment->url, PATHINFO_EXTENSION));
        $fileType = match($fileExtension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream'
        };

        // Determine document type based on title
        $documentType = 'other';
        $titleLower = strtolower($attachment->title);
        
        if (str_contains($titleLower, 'id') || str_contains($titleLower, 'nin')) {
            $documentType = 'id_card';
        } elseif (str_contains($titleLower, 'passport')) {
            $documentType = 'passport';
        } elseif (str_contains($titleLower, 'bank') || str_contains($titleLower, 'statement')) {
            $documentType = 'bank_statement';
        } elseif (str_contains($titleLower, 'payslip') || str_contains($titleLower, 'salary')) {
            $documentType = 'payslip';
        } elseif (str_contains($titleLower, 'utility') || str_contains($titleLower, 'bill')) {
            $documentType = 'utility_bill';
        } elseif (str_contains($titleLower, 'business') || str_contains($titleLower, 'license')) {
            $documentType = 'business_license';
        } elseif (str_contains($titleLower, 'tax') || str_contains($titleLower, 'tin')) {
            $documentType = 'tax_certificate';
        }

        // Get file size if file exists
        $filePath = public_path($attachment->url);
        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;

        // Insert into member_documents
        DB::table('member_documents')->insert([
            'member_id' => $attachment->member_id,
            'document_type' => $documentType,
            'document_name' => $attachment->title,
            'file_path' => $attachment->url,
            'file_type' => $fileType,
            'file_size' => $fileSize,
            'uploaded_by' => $attachment->added_by,
            'created_at' => $attachment->date_created,
            'updated_at' => $attachment->date_created,
        ]);

        echo "✓ Migrated: {$attachment->title} for member {$attachment->member_id}\n";
        $migrated++;

    } catch (Exception $e) {
        echo "✗ Error migrating attachment ID {$attachment->id}: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n========================================\n";
echo "Migration Complete!\n";
echo "========================================\n";
echo "Total attachments found: " . count($attachments) . "\n";
echo "Successfully migrated: {$migrated}\n";
echo "Already existed (skipped): {$skipped}\n";
echo "Errors: {$errors}\n";
echo "========================================\n";
