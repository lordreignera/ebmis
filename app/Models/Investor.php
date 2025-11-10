<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Investor extends Model
{
    use HasFactory;

    // Disable Laravel timestamps in favor of legacy structure
    public $timestamps = false;

    protected $fillable = [
        'title',
        'fname',
        'lname', 
        'address',
        'city',
        'country',
        'zip',
        'email',
        'passcode',
        'phone',
        'gender',
        'IDtype',
        'IDnumber',
        'status',
        'description',
        'photo_path',
        'image_path',
        'dob',
        'soft_delete',
        'del_user',
        'del_comments'
    ];

    protected $casts = [
        'title' => 'integer',
        'country' => 'integer',
        'status' => 'integer',
        'soft_delete' => 'integer',
        'del_user' => 'integer',
        'passcode' => 'integer'
    ];

    /**
     * Get the country that owns the investor
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country');
    }

    /**
     * Get all investments for this investor
     */
    public function investments()
    {
        return $this->hasMany(Investment::class, 'userid');
    }

    /**
     * Get active investments for this investor
     */
    public function activeInvestments()
    {
        return $this->investments()->where('status', 1);
    }

    /**
     * Get total investment amount
     */
    public function getTotalInvestmentAttribute()
    {
        return $this->investments()->sum('amount');
    }

    /**
     * Get total active investment amount
     */
    public function getActiveInvestmentAmountAttribute()
    {
        return $this->activeInvestments()->sum('amount');
    }

    /**
     * Get investor full name
     */
    public function getFullNameAttribute()
    {
        return $this->fname . ' ' . $this->lname;
    }

    /**
     * Get title name
     */
    public function getTitleNameAttribute()
    {
        $titles = [
            1 => 'Mr.',
            2 => 'Mrs.',
            3 => 'Ms.',
            4 => 'Dr.',
            5 => 'Prof.'
        ];

        return $titles[$this->title] ?? '';
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            0 => 'Pending',
            1 => 'Active',
            2 => 'Suspended',
            3 => 'Deactivated'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Scope for active investors
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for pending investors
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope for not deleted investors
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('soft_delete', 0);
    }

    /**
     * Scope for international investors
     */
    public function scopeInternational($query)
    {
        return $query->whereHas('country', function($q) {
            $q->where('name', '!=', 'Uganda'); // Assuming Uganda is local
        });
    }

    /**
     * Scope for local investors
     */
    public function scopeLocal($query)
    {
        return $query->whereHas('country', function($q) {
            $q->where('name', 'Uganda'); // Assuming Uganda is local
        });
    }
}