<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disbursement extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'loan_type',
        'code',
        'amount',
        'disbursement_date',
        'comments',
        'payment_type',
        'payment_medium',
        'account_name',
        'account_number',
        'investment_id',
        'inv_id',
        'assigned_to',
        'notes',
        'status',
        'reject_comments',
        'added_by',
        'date_approved',
        'medium'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'disbursement_date' => 'date',
        'date_approved' => 'datetime',
    ];

    /**
     * Get the personal loan that owns the disbursement (when loan_type = 1)
     */
    public function personalLoan()
    {
        return $this->belongsTo(PersonalLoan::class, 'loan_id')->where('loan_type', '1');
    }

    /**
     * Get the group loan that owns the disbursement (when loan_type = 2)
     */
    public function groupLoan()
    {
        return $this->belongsTo(GroupLoan::class, 'loan_id')->where('loan_type', '2');
    }

    /**
     * Get the investment account used for funding
     */
    public function investment()
    {
        return $this->belongsTo(Investment::class, 'investment_id');
    }

    /**
     * Get the user who added this disbursement
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get the user assigned to this disbursement
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the disbursement transaction record
     */
    public function transaction()
    {
        return $this->hasOne(DisbursementTransaction::class);
    }

    /**
     * Get the raw payment record
     */
    public function rawPayment()
    {
        return $this->hasOne(RawPayment::class, 'disbursement_id');
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
     * Get loan type name
     */
    public function getLoanTypeNameAttribute()
    {
        $loanTypes = [
            1 => 'Individual Loan',
            2 => 'Group Loan'
        ];

        return $loanTypes[$this->loan_type] ?? 'Unknown';
    }

    /**
     * Get medium name
     */
    public function getMediumNameAttribute()
    {
        $mediums = [
            'cash' => 'Cash',
            'bank' => 'Bank Transfer',
            'mobile' => 'Mobile Money',
            'cheque' => 'Cheque'
        ];

        return $mediums[$this->medium] ?? 'Unknown';
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