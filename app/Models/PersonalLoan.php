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

    /**
     * Get actual loan status based on disbursement and schedules
     */
    public function getActualStatus()
    {
        // Status codes:
        // 0 = Pending (Application submitted, not yet approved)
        // 1 = Approved (Approved but not disbursed)
        // 2 = Disbursed (Money given out)
        // 3 = Fully Paid/Closed
        // 4 = Rejected

        // If rejected, return rejected
        if ($this->status == 4) {
            return 'rejected';
        }

        // If pending, return pending
        if ($this->status == 0) {
            return 'pending';
        }

        // If approved but not disbursed
        if ($this->status == 1) {
            return 'approved';
        }

        // If marked as fully paid
        if ($this->status == 3) {
            return 'closed';
        }

        // If disbursed (status == 2), check schedules
        if ($this->status == 2) {
            // Use loaded relationship if available, otherwise query
            $schedules = $this->relationLoaded('schedules') ? $this->schedules : $this->schedules()->get();
            $schedulesCount = $schedules->count();
            
            // No schedules = closed
            if ($schedulesCount == 0) {
                return 'closed';
            }

            // Has schedules, check if any are unpaid
            $unpaidSchedules = $schedules->where('status', '!=', 1)->count();

            // If has unpaid schedules, loan is running
            if ($unpaidSchedules > 0) {
                return 'running';
            }

            // All schedules paid = closed
            return 'closed';
        }

        return 'unknown';
    }

    /**
     * Get actual loan status attribute
     */
    public function getActualStatusAttribute()
    {
        return $this->getActualStatus();
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
            default:
                return '<span class="badge bg-light text-dark">Unknown</span>';
        }
    }
}