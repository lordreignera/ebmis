<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use App\Traits\HandlesLegacyTimestamps;
use App\Traits\EastAfricanTime;

class Fee extends Model
{
    use HasFactory, HandlesLegacyTimestamps, EastAfricanTime;

    // Disable timestamps for old database compatibility
    public $timestamps = false;

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
        'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'datecreated' => 'datetime',
    ];

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