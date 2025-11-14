<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasFactory;

    protected $table = 'agency'; // Use the existing singular table name
    
    // Enable Laravel timestamps
    public $timestamps = true;
    
    // Specify timestamp column names
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'location',
        'description',
        'added_by',
        'isactive'
    ];

    protected $casts = [
        'isactive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who added this agency
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get all branches under this agency
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Scope for active agencies
     */
    public function scopeActive($query)
    {
        return $query->where('isactive', 1);
    }

    /**
     * Check if agency is active
     */
    public function isActive()
    {
        return $this->isactive == 1;
    }
}
