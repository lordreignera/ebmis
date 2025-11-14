<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'loan_type',
        'description',
        'max_amt',
        'interest',
        'period_type',
        'cash_sceurity', // Keep original typo for compatibility
        'account',
        'isactive',
        'added_by'
    ];

    protected $casts = [
        'isactive' => 'integer',
        'max_amt' => 'decimal:2',
        'interest' => 'decimal:2',
        'cash_sceurity' => 'decimal:2',
    ];

    // Disable Laravel timestamps completely for legacy compatibility
    public $timestamps = false;

    /**
     * Get the user who added this product
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get all personal loans using this product
     */
    public function personalLoans()
    {
        return $this->hasMany(PersonalLoan::class, 'product_type');
    }

    /**
     * Get all group loans using this product
     */
    public function groupLoans()
    {
        return $this->hasMany(GroupLoan::class, 'product_type');
    }

    /**
     * Get all charges for this product
     */
    public function charges()
    {
        return $this->hasMany(ProductCharge::class);
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('isactive', 1);
    }

    /**
     * Scope for loan products
     */
    public function scopeLoanProducts($query)
    {
        return $query->where('type', 1); // Assuming 1 = loan products
    }

    /**
     * Scope for savings products
     */
    public function scopeSavingsProducts($query)
    {
        return $query->where('type', 2); // Assuming 2 = savings products
    }

    /**
     * Scope for saving products (alias for backward compatibility)
     */
    public function scopeSavingProducts($query)
    {
        return $this->scopeSavingsProducts($query);
    }

    /**
     * Get product type name
     */
    public function getTypeNameAttribute()
    {
        $types = [
            1 => 'Loan Product',
            2 => 'Savings Product',
            3 => 'Investment Product'
        ];

        return $types[$this->type] ?? 'Unknown';
    }

    /**
     * Get loan type name
     */
    public function getLoanTypeNameAttribute()
    {
        $loanTypes = [
            1 => 'Personal Loan',
            2 => 'Group Loan',
            3 => 'Business Loan',
            4 => 'School Loan',
            5 => 'Student Loan',
            6 => 'Staff Loan'
        ];

        return $loanTypes[$this->loan_type] ?? 'Unknown';
    }

    /**
     * Get period type name
     */
    public function getPeriodTypeNameAttribute()
    {
        $periodTypes = [
            1 => 'Daily',
            2 => 'Weekly',
            3 => 'Monthly',
            4 => 'Yearly'
        ];

        return $periodTypes[$this->period_type] ?? 'Unknown';
    }
}