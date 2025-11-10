<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Repayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'details',
        'loan_id',
        'schedule_id',
        'amount',
        'date_created',
        'added_by',
        'status',
        'platform',
        'raw_message',
        'pay_status',
        'txn_id',
        'pay_message'
    ];

    protected $dates = [
        'date_created',
        'datecreated',
    ];

    // Use date_created instead of created_at for old database compatibility
    const CREATED_AT = 'date_created';
    const UPDATED_AT = null; // No updated_at in old table

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the loan that owns the repayment
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the loan schedule that owns the repayment
     */
    public function schedule()
    {
        return $this->belongsTo(LoanSchedule::class, 'schedule_id');
    }

    /**
     * Get the user who added this repayment
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get repayment type name
     */
    public function getTypeNameAttribute()
    {
        $types = [
            1 => 'Principal',
            2 => 'Interest',
            3 => 'Principal + Interest',
            4 => 'Penalty',
            5 => 'Charges'
        ];

        return $types[$this->type] ?? 'Unknown';
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            0 => 'Pending',
            1 => 'Confirmed',
            2 => 'Rejected',
            3 => 'Reversed'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Get platform name
     */
    public function getPlatformNameAttribute()
    {
        $platforms = [
            'cash' => 'Cash',
            'bank' => 'Bank Transfer',
            'mobile' => 'Mobile Money',
            'card' => 'Card Payment'
        ];

        return $platforms[$this->platform] ?? 'Unknown';
    }

    /**
     * Scope for confirmed repayments
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for pending repayments
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope for mobile money repayments
     */
    public function scopeMobileMoney($query)
    {
        return $query->where('platform', 'mobile');
    }

    /**
     * Scope for cash repayments
     */
    public function scopeCash($query)
    {
        return $query->where('platform', 'cash');
    }
}