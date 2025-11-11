<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsProduct extends Model
{
    use HasFactory;

    // Disable timestamps for old database compatibility
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'interest',
        'min_amt',
        'max_amt',
        'charge',
        'description',
        'account',
        'isactive'
    ];

    protected $casts = [
        'isactive' => 'boolean',
        'interest' => 'decimal:2',
        'min_amt' => 'decimal:2',
        'max_amt' => 'decimal:2',
        'charge' => 'decimal:2',
    ];

    /**
     * Get all savings using this product
     */
    public function savings()
    {
        return $this->hasMany(Saving::class, 'pdt_id');
    }

    /**
     * Scope for active savings products
     */
    public function scopeActive($query)
    {
        return $query->where('isactive', true);
    }

    /**
     * Get total savings amount for this product
     */
    public function getTotalSavingsAttribute()
    {
        return $this->savings()->confirmed()->sum('value');
    }

    /**
     * Get number of accounts using this product
     */
    public function getAccountsCountAttribute()
    {
        return $this->savings()->distinct('member_id')->count('member_id');
    }
}