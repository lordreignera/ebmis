<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\FileStorageService;

class MemberDocument extends Model
{
    protected $fillable = [
        'member_id', 'document_type', 'document_name',
        'file_path', 'file_type', 'file_size',
        'description', 'uploaded_by',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileUrlAttribute()
    {
        // Use FileStorageService to get correct URL (works for both local and Spaces)
        return FileStorageService::getFileUrl($this->file_path);
    }
    
    public function fileExists()
    {
        // Use FileStorageService to check existence (works for both local and Spaces)
        return FileStorageService::fileExists($this->file_path);
    }

    public function getFileSizeFormatted()
    {
        if (!$this->file_size) return 'Unknown';
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        while ($size > 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return round($size, 2) . ' ' . $units[$unit];
    }
}
