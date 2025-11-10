<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkSms extends Model
{
    use HasFactory;

    protected $table = 'bulk_sms';

    protected $fillable = [
        'title',
        'message',
        'recipient_type',
        'recipient_group',
        'recipients_count',
        'successful_count',
        'failed_count',
        'status',
        'scheduled_at',
        'completed_at',
        'sent_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'recipients_count' => 'integer',
        'successful_count' => 'integer',
        'failed_count' => 'integer',
    ];

    /**
     * Get the user who sent this bulk SMS
     */
    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get the SMS logs for this bulk SMS
     */
    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class, 'sms_id');
    }

    /**
     * Get the pending SMS logs
     */
    public function pendingSmsLogs()
    {
        return $this->hasMany(SmsLog::class, 'sms_id')->where('status', 'pending');
    }

    /**
     * Get the successful SMS logs
     */
    public function successfulSmsLogs()
    {
        return $this->hasMany(SmsLog::class, 'sms_id')->where('status', 'sent');
    }

    /**
     * Get the failed SMS logs
     */
    public function failedSmsLogs()
    {
        return $this->hasMany(SmsLog::class, 'sms_id')->where('status', 'failed');
    }

    /**
     * Check if the bulk SMS is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the bulk SMS is pending
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the bulk SMS is scheduled
     */
    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if the bulk SMS is currently sending
     */
    public function isSending()
    {
        return $this->status === 'sending';
    }

    /**
     * Get the success rate as a percentage
     */
    public function getSuccessRateAttribute()
    {
        if ($this->recipients_count == 0) {
            return 0;
        }
        
        return round(($this->successful_count / $this->recipients_count) * 100, 2);
    }

    /**
     * Get the failure rate as a percentage
     */
    public function getFailureRateAttribute()
    {
        if ($this->recipients_count == 0) {
            return 0;
        }
        
        return round(($this->failed_count / $this->recipients_count) * 100, 2);
    }

    /**
     * Scope to get completed bulk SMS
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending bulk SMS
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get scheduled bulk SMS
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope to get bulk SMS sent today
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}