<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    use HasFactory;

    // Use datecreated for created timestamp
    const CREATED_AT = 'datecreated';
    const UPDATED_AT = null; // No updated_at column
    
    public $timestamps = true;

    protected $fillable = [
        'loan_id',
        'member_id',
        'added_by',
        'signature',
        'signature_type',
        'signature_date'
    ];

    /**
     * Get the loan that owns the guarantor
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the member who is guarantor
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the user who added this guarantor
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get guarantor details
     */
    public function getGuarantorNameAttribute()
    {
        return $this->member->full_name ?? 'Unknown';
    }

    /**
     * Get guarantor contact
     */
    public function getGuarantorContactAttribute()
    {
        return $this->member->contact ?? 'N/A';
    }
}