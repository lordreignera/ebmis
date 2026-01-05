<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAccount extends Model
{
    use HasFactory;

    protected $table = 'system_accounts';
    
    // Primary key column name (capitalized in legacy database)
    protected $primaryKey = 'Id';
    
    // Disable timestamps for old database compatibility
    public $timestamps = false;

    protected $fillable = [
        'code',
        'sub_code',
        'name', 
        'category',
        'accountType',
        'accountSubType',
        'currency',
        'description',
        'parent_account',
        'running_balance',
        'is_cash_bank',
        'is_clearing',
        'is_loan_receivable',
        'allow_manual_posting',
        'added_by',
        'status'
    ];

    protected $casts = [
        'running_balance' => 'decimal:2',
        'parent_account' => 'integer',
        'status' => 'integer',
        'added_by' => 'integer',
        'is_cash_bank' => 'boolean',
        'is_clearing' => 'boolean',
        'is_loan_receivable' => 'boolean',
        'allow_manual_posting' => 'boolean'
    ];

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Get parent account
     */
    public function parent()
    {
        return $this->belongsTo(SystemAccount::class, 'parent_account');
    }

    /**
     * Get child accounts
     */
    public function children()
    {
        return $this->hasMany(SystemAccount::class, 'parent_account');
    }

    /**
     * Get the user who added this account
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get full account name with code
     */
    public function getFullNameAttribute()
    {
        $accountCode = $this->sub_code ?? $this->code;
        return $accountCode . ' - ' . $this->name;
    }

    /**
     * Scope for cash/bank accounts
     */
    public function scopeCashBank($query)
    {
        return $query->where('is_cash_bank', true);
    }

    /**
     * Scope for clearing accounts
     */
    public function scopeClearing($query)
    {
        return $query->where('is_clearing', true);
    }

    /**
     * Scope for loan receivable accounts
     */
    public function scopeLoanReceivable($query)
    {
        return $query->where('is_loan_receivable', true);
    }

    /**
     * Scope for system-controlled accounts (no manual posting)
     */
    public function scopeSystemControlled($query)
    {
        return $query->where('allow_manual_posting', false);
    }

    /**
     * Check if account can be manually posted
     */
    public function canManualPost(): bool
    {
        return $this->allow_manual_posting;
    }

    /**
     * Check if account is cash/bank
     */
    public function isCashBank(): bool
    {
        return $this->is_cash_bank;
    }

    /**
     * Check if account is clearing
     */
    public function isClearing(): bool
    {
        return $this->is_clearing;
    }

    /**
     * Check if account is loan receivable
     */
    public function isLoanReceivable(): bool
    {
        return $this->is_loan_receivable;
    }

    /**
     * Get next available sub code under a parent account
     * 
     * Example: Parent code 10000 has subcodes: 10020, 10021, 10030, 10031, 10040, 10041
     * Next available would be: 10050
     * 
     * @param string $parentCode The parent account code (e.g., '10000')
     * @return string The next available sub code (e.g., '10050')
     */
    public static function getNextSubCode(string $parentCode): string
    {
        // Get all subcodes under this parent
        $existingSubCodes = static::where('code', $parentCode)
            ->whereNotNull('sub_code')
            ->pluck('sub_code')
            ->map(function ($subCode) {
                // Extract just the last digits (e.g., 10020 -> 20)
                return (int) substr($subCode, -2);
            })
            ->toArray();

        if (empty($existingSubCodes)) {
            // First subcode: parent + 10 (e.g., 10000 -> 10010)
            return $parentCode . '10';
        }

        // Get the highest subcode and add 10
        $maxSubCode = max($existingSubCodes);
        $nextIncrement = $maxSubCode + 10;

        // Format: parent code + two-digit increment
        return $parentCode . str_pad($nextIncrement, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get suggested sub code when creating a child account
     * This is called from the controller to pre-fill the form
     * 
     * @param int|null $parentId The parent account ID
     * @return string|null The suggested sub code
     */
    public static function getSuggestedSubCode(?int $parentId): ?string
    {
        if (!$parentId) {
            return null;
        }

        $parent = static::find($parentId);
        if (!$parent) {
            return null;
        }

        return static::getNextSubCode($parent->code);
    }

    /**
     * Boot method to auto-generate sub_code if not provided
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            // If parent_account is set and sub_code is not provided, auto-generate it
            if ($account->parent_account && empty($account->sub_code)) {
                $parent = static::find($account->parent_account);
                if ($parent) {
                    $account->sub_code = static::getNextSubCode($parent->code);
                }
            }

            // If sub_code is provided but code is not, use parent's code
            if ($account->parent_account && empty($account->code)) {
                $parent = static::find($account->parent_account);
                if ($parent) {
                    $account->code = $parent->code;
                }
            }
        });
    }
}