<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'type',
        'value',
        'added_by',
        'isactive'
    ];

    protected $casts = [
        'isactive' => 'integer',
    ];

    // Disable Laravel timestamps completely for legacy compatibility
    public $timestamps = false;
    
    /**
     * Get the value attribute and clean it (remove % signs from legacy data)
     */
    public function getValueAttribute($value)
    {
        // Remove % sign if present and convert to float
        return floatval(str_replace('%', '', $value));
    }

    /**
     * Get the product that owns the charge
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who added this charge
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get charge type name
     */
    public function getTypeNameAttribute()
    {
        $types = [
            1 => 'Fixed Amount',
            2 => 'Percentage',
            3 => 'Per Day',
            4 => 'Per Month'
        ];

        return $types[$this->type] ?? 'Unknown';
    }

    /**
     * Scope for active charges
     */
    public function scopeActive($query)
    {
        return $query->where('isactive', 1);
    }
}