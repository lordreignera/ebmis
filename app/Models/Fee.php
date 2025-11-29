<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Fee extends Model
{
    use HasFactory;

    // Disable timestamps completely for old database compatibility
    public $timestamps = false;
    
    // Specify constant for timestamp columns to prevent Laravel from using them
    const CREATED_AT = null;
    const UPDATED_AT = null;

    protected $fillable = [
        'member_id',
        'loan_id',
        'fees_type_id',
        'payment_type',
        'amount',
        'description',
        'added_by',
        'payment_status',
        'payment_description',
        'payment_raw',
        'pay_ref',
        'status',
        'datecreated'
    ];

    protected $casts = [
        'datecreated' => 'datetime',
    ];

    /**
     * Get the amount attribute with proper type handling
     */
    public function getAmountAttribute($value)
    {
        // Handle empty strings and null values
        if (empty($value) && $value !== '0') {
            return 0;
        }
        return (float) $value;
    }

    /**
     * Get the member that owns the fee
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the loan that owns the fee (if any)
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the fee type
     */
    public function feeType()
    {
        return $this->belongsTo(FeeType::class, 'fees_type_id');
    }

    /**
     * Get the user who added this fee
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get payment type name
     */
    public function getPaymentTypeNameAttribute()
    {
        $paymentTypes = [
            1 => 'Cash',
            2 => 'Bank Transfer',
            3 => 'Mobile Money',
            4 => 'Card Payment'
        ];

        return $paymentTypes[$this->payment_type] ?? 'Unknown';
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            0 => 'Pending',
            1 => 'Paid',
            2 => 'Overdue',
            3 => 'Waived'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Scope for paid fees
     */
    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for pending fees
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope for overdue fees
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 2);
    }
}