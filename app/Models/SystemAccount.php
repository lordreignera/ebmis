<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemAccount extends Model
{
    use HasFactory;

    protected $table = 'system_accounts';
    
    // Use old database timestamp column
    const CREATED_AT = 'date_created';
    const UPDATED_AT = null; // No updated_at in old database

    protected $fillable = [
        'code',
        'name', 
        'accountType',
        'accountSubType',
        'currency',
        'description',
        'parent_account',
        'running_balance',
        'added_by',
        'status'
    ];

    protected $casts = [
        'running_balance' => 'decimal:2',
        'parent_account' => 'integer',
        'status' => 'integer',
        'added_by' => 'integer'
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
        return $this->code . ' - ' . $this->name;
    }
}