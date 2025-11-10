<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'url',
        'member_id',
        'loan_id',
        'added_by',
        'status'
    ];

    /**
     * Get the member that owns the attachment
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the loan that owns the attachment (if any)
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the user who added this attachment
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Rejected'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Get file extension
     */
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->url, PATHINFO_EXTENSION);
    }

    /**
     * Get file size (if available)
     */
    public function getFileSizeAttribute()
    {
        if (file_exists(storage_path('app/public/' . $this->url))) {
            return filesize(storage_path('app/public/' . $this->url));
        }
        return null;
    }

    /**
     * Scope for approved attachments
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for pending attachments
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }
}