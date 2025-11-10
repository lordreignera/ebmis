<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Saving extends Model
{
    use HasFactory;

    // Use datecreated for timestamps (old database compatibility)
    const CREATED_AT = 'datecreated';
    const UPDATED_AT = null; // No updated_at in old database

    protected $fillable = [
        'member_id',
        'branch_id',
        'pdt_id',
        'value',
        'sperson',
        'sdate',
        'description',
        'added_by',
        'status',
        'platform',
        'txn_id',
        'pay_status',
        'pay_message',
        'datecreated'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'sdate' => 'date',
    ];

    /**
     * Get the member that owns the saving
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the branch that owns the saving
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who added this saving
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get the savings product
     */
    public function product()
    {
        return $this->belongsTo(SavingsProduct::class, 'pdt_id');
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
     * Get formatted amount
     */
    public function getAmountAttribute()
    {
        return $this->value;
    }

    /**
     * Scope for confirmed savings
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for pending savings
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope for mobile money savings
     */
    public function scopeMobileMoney($query)
    {
        return $query->where('platform', 'mobile');
    }

    /**
     * Scope for cash savings
     */
    public function scopeCash($query)
    {
        return $query->where('platform', 'cash');
    }
}