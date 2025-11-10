<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
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
        'comments',
        'charge_type',
        'date_closed',
    ];

    protected $casts = [
        'verified' => 'integer',
        'principal' => 'decimal:2',
        'installment' => 'decimal:2',
        'interest' => 'decimal:2',
        'date_closed' => 'datetime',
        'datecreated' => 'datetime',
    ];

    public $timestamps = false;

    /**
     * Get the member that owns the loan (for personal loans)
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the product associated with the loan
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_type');
    }

    /**
     * Get the branch associated with the loan
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
     * Get the repayments for the loan
     */
    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }

    /**
     * Get the loan schedules
     */
    public function schedules()
    {
        return $this->hasMany(LoanSchedule::class);
    }

    /**
     * Scope to get disbursed loans
     */
    public function scopeDisbursed($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Scope to get active loans
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [1, 2]); // Approved or Disbursed
    }

    /**
     * Get borrower name (works for both personal and group loans)
     */
    public function getBorrowerNameAttribute()
    {
        if ($this->member_id) {
            $member = $this->member;
            return $member ? $member->fname . ' ' . $member->lname : 'Unknown Member';
        }
        return 'Unknown Borrower';
    }

    /**
     * Get borrower contact
     */
    public function getBorrowerContactAttribute()
    {
        if ($this->member_id) {
            $member = $this->member;
            return $member ? $member->contact : '';
        }
        return '';
    }
}
