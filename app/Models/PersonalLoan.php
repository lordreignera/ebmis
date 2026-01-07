<?php

namespace App\Models;

use App\Traits\HasLoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalLoan extends Model
{
    use HasFactory, HasLoanStatus;

    protected $table = 'personal_loans';
    
    protected $fillable = [
        'member_id',
        'product_type',
        'code',
        'interest',
        'interest_method',
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
        'signature_comments',
        'loan_purpose',
        'cash_account_number',
        'cash_account_name',
        'immovable_assets',
        'moveable_assets',
        'intellectual_property',
        'stocks_collateral',
        'livestock_collateral',
        'group_banker_name',
        'group_banker_nin',
        'group_banker_occupation',
        'group_banker_residence',
        'witness_name',
        'witness_nin',
        'witness_signature',
        'witness_signature_type',
        'witness_signature_date',
        'borrower_signature',
        'borrower_signature_type',
        'borrower_signature_date',
        'lender_signature',
        'lender_signature_type',
        'lender_signature_date',
        'lender_signed_by',
        'lender_title',
        'signed_agreement_path',
        'agreement_finalized_at'
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
     * Get the user who rejected this loan
     */
    public function rejectedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'rejected_by');
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

    // getActualStatus() and related methods now provided by HasLoanStatus trait

    /**
     * Get period type from product relationship
     * 1 = Weekly, 2 = Monthly, 3 = Daily
     */
    public function getPeriodTypeAttribute()
    {
        if ($this->relationLoaded('product') && $this->product) {
            return $this->product->period_type;
        }
        
        // Lazy load if not already loaded
        return $this->product->period_type ?? null;
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute()
    {
        $actualStatus = $this->getActualStatus();

        switch ($actualStatus) {
            case 'pending':
                return '<span class="badge bg-warning">Pending</span>';
            case 'approved':
                return '<span class="badge bg-info">Approved</span>';
            case 'running':
                return '<span class="badge bg-success">Running</span>';
            case 'closed':
                return '<span class="badge bg-secondary">Closed</span>';
            case 'rejected':
                return '<span class="badge bg-danger">Rejected</span>';
            case 'restructured':
                return '<span class="badge bg-primary">Restructured</span>';
            case 'stopped':
                return '<span class="badge bg-dark">Stopped</span>';
            default:
                return '<span class="badge bg-light text-dark">Unknown</span>';
        }
    }
}