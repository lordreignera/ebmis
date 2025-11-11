<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentLoan extends Model
{
    use HasFactory;

    protected $table = 'student_loans';

    protected $fillable = [
        'student_id',
        'school_id',
        'product_type',
        'branch_id',
        'added_by',
        'code',
        'interest',
        'period',
        'principal',
        'installment',
        'status',
        'verified',
        'repay_strategy',
        'business_name',
        'business_contact',
        'repay_period',
        'business_license',
        'bank_statement',
        'business_photos',
        'charge_type',
        'approved_by',
        'date_approved',
        'rejected_by',
        'date_rejected',
        'Rcomments',
        'date_closed',
        'restructured',
        'is_esign',
        'sign_code',
        'assigned_to',
        'comments',
        'OLoanID',
        'loan_purpose',
        'datecreated',
    ];

    protected $casts = [
        'principal' => 'decimal:2',
        'installment' => 'decimal:2',
        'date_approved' => 'datetime',
        'date_rejected' => 'datetime',
        'date_closed' => 'datetime',
        'datecreated' => 'datetime',
        'is_esign' => 'boolean',
    ];

    /**
     * Get the student that owns the loan
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the school
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the product type
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_type');
    }

    /**
     * Get the branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the user who added the loan
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get the user who approved the loan
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who rejected the loan
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the user assigned to the loan
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the loan schedules
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class, 'loan_id');
    }

    /**
     * Get the disbursements
     */
    public function disbursements(): HasMany
    {
        return $this->hasMany(Disbursement::class, 'loan_id');
    }

    /**
     * Get the fees associated with this loan
     */
    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class, 'loan_id');
    }

    /**
     * Scope to get pending loans
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope to get approved loans
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get disbursed loans
     */
    public function scopeDisbursed($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Scope to get completed loans
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 3);
    }

    /**
     * Scope to get rejected loans
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 4);
    }

    /**
     * Check if loan is pending
     */
    public function isPending(): bool
    {
        return $this->status === 0;
    }

    /**
     * Check if loan is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if loan is disbursed
     */
    public function isDisbursed(): bool
    {
        return $this->status === 2;
    }

    /**
     * Check if loan is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 3;
    }

    /**
     * Check if loan is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 4;
    }
}
