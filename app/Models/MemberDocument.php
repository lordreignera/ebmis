<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        // NEW uploads: Already have 'uploads/' prefix
        if (strpos($this->file_path, 'uploads/') === 0) {
            return asset($this->file_path);
        }
        
        // OLD uploads: Stored as 'member-documents/X/file.pdf' without 'uploads/' prefix
        // Check if file exists in new location (public/uploads/member-documents/)
        $pathWithUploads = 'uploads/' . $this->file_path;
        if (file_exists(public_path($pathWithUploads))) {
            return asset($pathWithUploads);
        }
        
        // Fall back to old storage location (requires symlink)
        if (file_exists(storage_path('app/public/' . $this->file_path))) {
            return asset('storage/' . $this->file_path);
        }
        
        // File not found anywhere
        return null;
    }
    
    public function fileExists()
    {
        // Check NEW location with uploads/ prefix
        if (strpos($this->file_path, 'uploads/') === 0) {
            return file_exists(public_path($this->file_path));
        }
        
        // Check OLD location converted to new location
        $pathWithUploads = 'uploads/' . $this->file_path;
        if (file_exists(public_path($pathWithUploads))) {
            return true;
        }
        
        // Check OLD storage location
        return file_exists(storage_path('app/public/' . $this->file_path));
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
