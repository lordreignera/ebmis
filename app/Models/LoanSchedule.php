<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'payment_date',
        'payment',
        'interest',
        'principal',
        'balance',
        'status',
        'date_modified',
        'raw_message',
        'txn_id',
        'date_cleared'
    ];

    protected $casts = [
        'payment' => 'decimal:2',
        'interest' => 'decimal:2',
        'principal' => 'decimal:2',
        'balance' => 'decimal:2',
        'payment_date' => 'date',
        'date_cleared' => 'datetime',
    ];

    /**
     * Get the personal loan that owns the schedule
     */
    public function personalLoan()
    {
        return $this->belongsTo(\App\Models\PersonalLoan::class, 'loan_id');
    }

    /**
     * Get the group loan that owns the schedule
     */
    public function groupLoan()
    {
        return $this->belongsTo(\App\Models\GroupLoan::class, 'loan_id');
    }

    /**
     * Get all repayments for this schedule
     */
    public function repayments()
    {
        return $this->hasMany(Repayment::class, 'schedule_id');
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            0 => 'Pending',
            1 => 'Paid',
            2 => 'Overdue',
            3 => 'Partially Paid'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Get due date attribute
     */
    public function getDueDateAttribute()
    {
        return $this->payment_date;
    }

    /**
     * Get amount paid for this schedule
     */
    public function getAmountPaidAttribute()
    {
        return $this->repayments()->confirmed()->sum('amount');
    }

    /**
     * Get outstanding amount for this schedule
     */
    public function getOutstandingAmountAttribute()
    {
        return $this->payment - $this->amount_paid;
    }

    /**
     * Check if schedule is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->payment_date < now() && $this->status != 1;
    }

    /**
     * Check if schedule is fully paid
     */
    public function getIsFullyPaidAttribute()
    {
        return $this->amount_paid >= $this->payment;
    }

    /**
     * Get days overdue
     */
    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) return 0;
        
        return now()->diffInDays($this->payment_date);
    }

    /**
     * Scope for paid schedules
     */
    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for pending schedules
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope for overdue schedules
     */
    public function scopeOverdue($query)
    {
        return $query->where('payment_date', '<', now())->where('status', '!=', 1);
    }

    /**
     * Scope for due today
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    /**
     * Scope for due this week
     */
    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('payment_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }
}