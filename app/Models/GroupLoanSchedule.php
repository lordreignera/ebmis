<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupLoanSchedule extends Model
{
    use HasFactory;

    protected $table = 'group_loan_schedules';

    protected $fillable = [
        'loan_id',
        'payment_date',
        'payment',
        'interest',
        'principal',
        'balance',
        'status'
    ];

    protected $casts = [
        'payment' => 'decimal:2',
        'interest' => 'decimal:2',
        'principal' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    /**
     * Get the group loan that owns this schedule
     */
    public function groupLoan()
    {
        return $this->belongsTo(GroupLoan::class, 'loan_id');
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
     * Scope for pending schedules
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope for paid schedules
     */
    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for overdue schedules
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Check if payment is due
     */
    public function isDue()
    {
        return $this->payment_date <= now()->format('Y-m-d') && $this->status == 0;
    }

    /**
     * Check if payment is overdue
     */
    public function isOverdue()
    {
        return $this->payment_date < now()->format('Y-m-d') && $this->status == 0;
    }
}