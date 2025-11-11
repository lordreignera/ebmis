<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Country extends Model
{
    use HasFactory;

    // Disable timestamps completely for old database compatibility
    public $timestamps = false;

    protected $fillable = [
        'name',
        'code',
        'currency',
        'flag',
        'is_active',
        'status' // Old database column
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status' => 'integer',
    ];

    /**
     * Check if the table has the new is_active column
     */
    private static function hasIsActiveColumn(): bool
    {
        static $hasIsActive = null;
        
        if ($hasIsActive === null) {
            $hasIsActive = Schema::hasColumn('countries', 'is_active');
        }
        
        return $hasIsActive;
    }

    /**
     * Scope for active countries
     */
    public function scopeActive($query)
    {
        if (self::hasIsActiveColumn()) {
            return $query->where('is_active', true);
        } else {
            // Fall back to status column for old database
            return $query->where('status', 1);
        }
    }

    /**
     * Check if country is active
     */
    public function getIsActiveAttribute($value)
    {
        // If new column exists, use it
        if (self::hasIsActiveColumn()) {
            return (bool) $value;
        } else {
            // Fall back to status for old database
            return $this->status == 1;
        }
    }

    /**
     * Get all branches in this country
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get all members in this country
     */
    public function members()
    {
        return $this->hasMany(Member::class);
    }
}