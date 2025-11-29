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
            return url($this->file_path);
        }
        
        // Handle new files using Laravel storage
        return \Storage::url($this->file_path);
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
