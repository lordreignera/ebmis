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
        // Handle legacy files from old system (stored in public/uploads/)
        if (strpos($this->file_path, 'uploads/') === 0) {
            // Check if file exists in public/uploads
            $publicPath = public_path($this->file_path);
            if (file_exists($publicPath)) {
                return url($this->file_path);
            }
            // File doesn't exist, return null to show error
            return null;
        }
        
        // Handle new files using Laravel storage (storage/app/public/)
        // Use asset('storage/...') pattern same as loan documents for consistency
        return asset('storage/' . $this->file_path);
    }
    
    public function fileExists()
    {
        if (strpos($this->file_path, 'uploads/') === 0) {
            return file_exists(public_path($this->file_path));
        }
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
