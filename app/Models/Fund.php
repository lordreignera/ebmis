<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fund extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'description',
        'donor_name',
        'start_date',
        'end_date',
        'total_amount',
        'disbursed_amount',
        'available_amount',
        'is_active',
        'added_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'available_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'fund_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAvailable($query)
    {
        return $query->where('available_amount', '>', 0);
    }

    /**
     * Check if fund has sufficient balance
     */
    public function hasSufficientBalance($amount)
    {
        return $this->available_amount >= $amount;
    }

    /**
     * Record a disbursement from this fund
     */
    public function recordDisbursement($amount)
    {
        $this->disbursed_amount += $amount;
        $this->available_amount -= $amount;
        $this->save();
    }

    /**
     * Record a repayment to this fund
     */
    public function recordRepayment($amount)
    {
        $this->disbursed_amount -= $amount;
        $this->available_amount += $amount;
        $this->save();
    }

    /**
     * Get utilization percentage
     */
    public function getUtilizationPercentageAttribute()
    {
        if ($this->total_amount <= 0) {
            return 0;
        }
        return round(($this->disbursed_amount / $this->total_amount) * 100, 2);
    }

    /**
     * Check if fund is fully utilized
     */
    public function isFullyUtilized()
    {
        return $this->available_amount <= 0;
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeAttribute()
    {
        if (!$this->is_active) {
            return 'secondary';
        }
        
        if ($this->isFullyUtilized()) {
            return 'danger';
        }
        
        if ($this->utilization_percentage >= 80) {
            return 'warning';
        }
        
        return 'success';
    }
}
