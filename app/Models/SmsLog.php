<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    use HasFactory;

    protected $table = 'bulk_sms_users';

    protected $fillable = [
        'sms_id',
        'member_id',
        'phone',
        'message',
        'status',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Get the bulk SMS campaign this log belongs to
     */
    public function bulkSms()
    {
        return $this->belongsTo(BulkSms::class, 'sms_id');
    }

    /**
     * Get the member this SMS was sent to
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Check if the SMS was sent successfully
     */
    public function isSent()
    {
        return $this->status === 'sent';
    }

    /**
     * Check if the SMS failed to send
     */
    public function isFailed()
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the SMS is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Scope to get sent SMS logs
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to get failed SMS logs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get pending SMS logs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get SMS logs sent today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('sent_at', today());
    }
}