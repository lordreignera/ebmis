<?php

namespace Tests\Unit;

use App\Services\FileStorageService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileStorageServiceTest extends TestCase
{
    public function test_executable_upload_extensions_are_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This file type is not allowed.');

        FileStorageService::storeFile(
            UploadedFile::fake()->create('payload.php', 1, 'text/plain'),
            'security-test'
        );
    }

    public function test_safe_uploads_use_random_safe_filenames(): void
    {
        $path = FileStorageService::storeFile(
            UploadedFile::fake()->create('member-document.pdf', 1, 'application/pdf'),
            'security-test'
        );

        try {
            $this->assertMatchesRegularExpression(
                '#^uploads/security-test/[a-f0-9]{32}\.pdf$#',
                $path
            );
            $this->assertStringNotContainsString('member-document', $path);
        } finally {
            FileStorageService::deleteFile($path);
        }
    }
}
