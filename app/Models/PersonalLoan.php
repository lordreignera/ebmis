<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalLoan extends Model
{
    use HasFactory;

    protected $table = 'personal_loans';
    
    protected $fillable = [
        'member_id',
        'product_type',
        'code',
        'interest',
        'period',
        'principal',
        'installment',
        'status',
        'verified',
        'branch_id',
        'added_by',
        'approved_by',
        'date_approved',
        'rejected_by',
        'date_rejected',
        'datecreated',
        'trading_file',
        'bank_file',
        'business_file',
        'repay_strategy',
        'repay_name',
        'repay_address',
        'comments',
        'charge_type',
        'date_closed',
        'sign_code',
        'OLoanID',
        'Rcomments',
        'restructured',
        'assigned_to',
        'is_esign',
        'otp_code',
        'otp_expires_at',
        'signature_status',
        'signature_date',
        'signature_comments'
    ];

    protected $casts = [
        'verified' => 'integer',
        'restructured' => 'integer',
        'principal' => 'decimal:2',
        'installment' => 'decimal:2',
        'interest' => 'decimal:2',
        'date_closed' => 'datetime',
        'date_approved' => 'datetime',
        'date_rejected' => 'datetime',
        'datecreated' => 'datetime',
        'is_esign' => 'boolean',
        'otp_expires_at' => 'datetime',
        'signature_date' => 'datetime',
    ];

    // Disable Laravel timestamps completely for legacy compatibility
    public $timestamps = false;

    /**
     * Get the member that owns the loan
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the product type for this loan
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_type');
    }

    /**
     * Get the branch that owns the loan
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get all disbursements for this loan
     */
    public function disbursements()
    {
        return $this->hasMany(Disbursement::class, 'loan_id')->where('loan_type', '1');
    }

    /**
     * Get all repayments for this loan
     */
    public function repayments()
    {
        return $this->hasMany(Repayment::class, 'loan_id');
    }

    /**
     * Get all schedules for this loan
     */
    public function schedules()
    {
        return $this->hasMany(\App\Models\LoanSchedule::class, 'loan_id');
    }

    /**
     * Get the user who added this loan
     */
    public function addedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'added_by');
    }

    /**
     * Get the user who approved this loan
     */
    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Get the user assigned to this loan
     */
    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /**
     * Get all guarantors for this loan
     */
    public function guarantors()
    {
        return $this->hasMany(\App\Models\Guarantor::class, 'loan_id');
    }

    /**
     * Get all charges for this loan
     */
    public function charges()
    {
        return $this->hasMany(\App\Models\LoanCharge::class, 'loan_id');
    }

    /**
     * Check if loan is disbursed
     */
    public function getIsDisbursedAttribute()
    {
        return $this->verified == 1;
    }

    /**
     * Check if loan is pending
     */
    public function getIsPendingAttribute()
    {
        return $this->verified == 0;
    }

    /**
     * Check if loan is rejected
     */
    public function getIsRejectedAttribute()
    {
        return $this->verified == 2;
    }

    /**
     * Get outstanding balance
     */
    public function getOutstandingAmountAttribute()
    {
        $totalDisbursed = $this->disbursements()->where('status', 2)->sum('amount');
        $totalPaid = $this->repayments()->sum('amount');
        return $totalDisbursed - $totalPaid;
    }
}