<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    use HasFactory;

    protected $table = 'fees_types';

    protected $fillable = [
        'name',
        'account',
        'added_by',
        'isactive',
        'required_disbursement'
    ];

    protected $casts = [
        'isactive' => 'boolean',
    ];

    /**
     * Get the user who added this fee type
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get all fees of this type
     */
    public function fees()
    {
        return $this->hasMany(Fee::class, 'fees_type_id');
    }

    /**
     * Scope for active fee types
     */
    public function scopeActive($query)
    {
        return $query->where('isactive', true);
    }

    /**
     * Scope for fee types that require disbursement
     */
    public function scopeRequiresDisbursement($query)
    {
        return $query->where('required_disbursement', 1);
    }
}