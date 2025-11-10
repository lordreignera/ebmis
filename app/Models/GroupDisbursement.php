<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupDisbursement extends Model
{
    use HasFactory;

    protected $table = 'group_disbursement';

    protected $fillable = [
        'loan_id',
        'loan_type',
        'amount',
        'comments',
        'payment_type',
        'account_name',
        'account_number',
        'inv_id',
        'status',
        'reject_comments',
        'added_by',
        'date_approved'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date_approved' => 'datetime',
    ];

    /**
     * Get the group loan that owns the disbursement
     */
    public function groupLoan()
    {
        return $this->belongsTo(GroupLoan::class, 'loan_id');
    }

    /**
     * Get the investment account used for funding
     */
    public function investment()
    {
        return $this->belongsTo(Investment::class, 'inv_id');
    }

    /**
     * Get the user who added this disbursement
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
            2 => 'Disbursed',
            3 => 'Rejected',
            4 => 'Cancelled'
        ];

        return $statuses[$this->status] ?? 'Unknown';
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
            4 => 'Cheque'
        ];

        return $paymentTypes[$this->payment_type] ?? 'Unknown';
    }

    /**
     * Scope for approved disbursements
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for disbursed
     */
    public function scopeDisbursed($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Scope for pending disbursements
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope for rejected disbursements
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 3);
    }

    /**
     * Scope for cash disbursements
     */
    public function scopeCash($query)
    {
        return $query->where('payment_type', 1);
    }

    /**
     * Scope for mobile money disbursements
     */
    public function scopeMobileMoney($query)
    {
        return $query->where('payment_type', 3);
    }
}