<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'charge_name',
        'charge_type',
        'charge_value',
        'actual_value',
        'added_by'
    ];

    protected $casts = [
        'charge_value' => 'decimal:2',
        'actual_value' => 'decimal:2',
    ];

    /**
     * Get the loan that owns the charge
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
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
    public function getChargeTypeNameAttribute()
    {
        $chargeTypes = [
            1 => 'Fixed Amount',
            2 => 'Percentage',
            3 => 'Per Day',
            4 => 'Per Month'
        ];

        return $chargeTypes[$this->charge_type] ?? 'Unknown';
    }

    /**
     * Calculate actual charge value
     */
    public function getCalculatedAmountAttribute()
    {
        if ($this->charge_type == 1) {
            // Fixed amount
            return $this->charge_value;
        } elseif ($this->charge_type == 2) {
            // Percentage of loan amount
            return ($this->loan->principal * $this->charge_value) / 100;
        }
        
        return $this->actual_value;
    }
}