<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Investment extends Model
{
    use HasFactory;

    protected $table = 'investment'; // Singular table name as per legacy

    // Disable Laravel timestamps in favor of legacy structure  
    public $timestamps = false;

    protected $fillable = [
        'userid',
        'type',
        'name',
        'amount',
        'period',
        'percentage',
        'start',
        'end',
        'interest',
        'details',
        'status',
        'added_by'
    ];

    protected $casts = [
        'userid' => 'integer',
        'type' => 'integer',
        'status' => 'integer'
    ];

    /**
     * Get the investor that owns the investment
     */
    public function investor()
    {
        return $this->belongsTo(Investor::class, 'userid');
    }

    /**
     * Get investment type name
     */
    public function getTypeNameAttribute()
    {
        $types = [
            1 => 'Standard Interest',
            2 => 'Compounding Interest'
        ];

        return $types[$this->type] ?? 'Unknown';
    }

    /**
     * Get investment status name
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            0 => 'Pending',
            1 => 'Active',
            2 => 'Completed',
            3 => 'Cancelled'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get formatted interest
     */
    public function getFormattedInterestAttribute()
    {
        return '$' . number_format($this->interest, 2);
    }

    /**
     * Get total return (principal + interest)
     */
    public function getTotalReturnAttribute()
    {
        return (float)$this->amount + (float)$this->interest;
    }

    /**
     * Get formatted total return
     */
    public function getFormattedTotalReturnAttribute()
    {
        return '$' . number_format($this->total_return, 2);
    }

    /**
     * Get start date as Carbon instance
     */
    public function getStartDateAttribute()
    {
        try {
            return Carbon::createFromFormat('m/d/Y', $this->start);
        } catch (\Exception $e) {
            return Carbon::parse($this->start);
        }
    }

    /**
     * Get end date as Carbon instance
     */
    public function getEndDateAttribute()
    {
        try {
            return Carbon::createFromFormat('m/d/Y', $this->end);
        } catch (\Exception $e) {
            return Carbon::parse($this->end);
        }
    }

    /**
     * Check if investment is expired
     */
    public function getIsExpiredAttribute()
    {
        return $this->end_date && $this->end_date->isPast();
    }

    /**
     * Get days remaining
     */
    public function getDaysRemainingAttribute()
    {
        if ($this->end_date && !$this->is_expired) {
            return $this->end_date->diffInDays(now());
        }
        return 0;
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->start_date && $this->end_date) {
            $totalDays = $this->start_date->diffInDays($this->end_date);
            $elapsedDays = $this->start_date->diffInDays(now());
            
            if ($totalDays > 0) {
                $percentage = ($elapsedDays / $totalDays) * 100;
                return min(100, max(0, $percentage));
            }
        }
        return 0;
    }

    /**
     * Calculate interest based on type
     */
    public static function calculateInterest($amount, $period, $type)
    {
        $amount = (float)$amount;
        $period = (int)$period;
        
        if ($type == 1) { // Standard Interest
            $rate = 0;
            if ($period >= 1 && $period <= 2) {
                $rate = 2.56;
            } elseif ($period > 2 && $period <= 4) {
                $rate = 3.7;
            } elseif ($period > 4 && $period <= 7) {
                $rate = 4.84;
            }
            
            $annualProfit = ($rate / 100) * $amount;
            $totalInterest = $annualProfit * $period;
            
            return [
                'rate' => $rate,
                'annual_profit' => $annualProfit,
                'total_interest' => $totalInterest,
                'total_return' => $amount + $totalInterest
            ];
        } elseif ($type == 2) { // Compound Interest
            $rate = 0;
            if ($period >= 3 && $period <= 4) {
                $rate = 7.5;
            } elseif ($period > 4 && $period <= 7) {
                $rate = 10.55;
            }
            
            $futureValue = $amount * pow((1.0 + ($rate / 100)), $period);
            $totalInterest = $futureValue - $amount;
            
            return [
                'rate' => $rate,
                'annual_profit' => 0, // Not applicable for compound interest
                'total_interest' => $totalInterest,
                'total_return' => $futureValue
            ];
        }
        
        return [
            'rate' => 0,
            'annual_profit' => 0,
            'total_interest' => 0,
            'total_return' => $amount
        ];
    }

    /**
     * Scope for active investments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for pending investments
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope for completed investments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Scope for standard interest investments
     */
    public function scopeStandardInterest($query)
    {
        return $query->where('type', 1);
    }

    /**
     * Scope for compound interest investments
     */
    public function scopeCompoundInterest($query)
    {
        return $query->where('type', 2);
    }
}