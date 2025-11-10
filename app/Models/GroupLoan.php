<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupLoan extends Model
{
    use HasFactory;

    protected $table = 'group_loans';
    
    // Disable Laravel timestamps completely for legacy compatibility
    public $timestamps = false;

    protected $fillable = [
        'group_id',
        'product_type',
        'code',
        'interest',
        'period',
        'principal',
        'status',
        'verified',
        'branch_id',
        'added_by',
        'comments',
        'charge_type',
        'date_closed',
        'is_esign',
        'otp_code',
        'otp_expires_at',
        'signature_status',
        'signature_date',
        'signature_comments'
    ];

    protected $casts = [
        'verified' => 'integer',
        'principal' => 'decimal:2',
        'interest' => 'decimal:2',
        'date_closed' => 'datetime',
        'datecreated' => 'datetime',
        'is_esign' => 'boolean',
        'otp_expires_at' => 'datetime',
        'signature_date' => 'datetime',
    ];

    /**
     * Get the group that owns the loan
     */
    public function group()
    {
        return $this->belongsTo(Group::class);
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
     * Get the user who added this loan
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get all loan members (individual allocations)
     */
    public function loanMembers()
    {
        return $this->hasMany(GroupLoanMember::class, 'group_loan_id');
    }

    /**
     * Get all repayments for this group loan
     */
    public function repayments()
    {
        return $this->hasMany(GroupRepayment::class, 'group_loan_id');
    }

    /**
     * Get all disbursements for this group loan
     */
    public function disbursements()
    {
        return $this->hasMany(GroupDisbursement::class, 'loan_id');
    }

    /**
     * Get all loan schedules for this group loan
     */
    public function schedules()
    {
        return $this->hasMany(GroupLoanSchedule::class, 'loan_id');
    }

    /**
     * Get loan status name
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            0 => 'Pending',
            1 => 'Approved',
            2 => 'Disbursed',
            3 => 'Closed',
            4 => 'Rejected',
            5 => 'Written Off'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Get total amount paid
     */
    public function getTotalPaidAttribute()
    {
        return $this->repayments()->sum('amount');
    }

    /**
     * Get outstanding balance
     */
    public function getOutstandingBalanceAttribute()
    {
        $totalDisbursed = $this->disbursements()->sum('amount');
        $totalPaid = $this->total_paid;
        return $totalDisbursed - $totalPaid;
    }

    /**
     * Scope for active group loans
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [1, 2]); // Approved or Disbursed
    }

    /**
     * Scope for disbursed group loans
     */
    public function scopeDisbursed($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Scope for verified group loans
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }
}