<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    // Enable Laravel timestamps
    public $timestamps = true;
    
    // Specify timestamp column names
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'country',
        'description',
        'is_active',
        'added_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who added this branch
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get the country that owns the branch
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get all members in this branch
     */
    public function members()
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Get all personal loans in this branch
     */
    public function personalLoans()
    {
        return $this->hasMany(\App\Models\PersonalLoan::class);
    }

    /**
     * Get all group loans in this branch
     */
    public function groupLoans()
    {
        return $this->hasMany(\App\Models\GroupLoan::class);
    }

    /**
     * Get all loans in this branch (personal + group loans)
     */
    public function loans()
    {
        return $this->personalLoans();
    }

    /**
     * Get all groups in this branch
     */
    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    /**
     * Get all savings accounts in this branch
     */
    public function savings()
    {
        return $this->hasMany(Saving::class);
    }

    /**
     * Scope for active branches
     * Handle both old database (no is_active column) and new database (with is_active column)
     */
    public function scopeActive($query)
    {
        // Check if is_active column exists
        if (\Schema::hasColumn('branches', 'is_active')) {
            return $query->where('is_active', true);
        }
        
        // For old database structure, return all branches (they were all considered active)
        return $query;
    }
}