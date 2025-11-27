<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LateFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'schedule_id',
        'member_id',
        'amount',
        'days_overdue',
        'periods_overdue',
        'period_type',
        'schedule_due_date',
        'calculated_date',
        'calculation_details',
        'status',
        'waiver_reason',
        'waived_at',
        'waived_by',
        'paid_at',
        'payment_reference'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'days_overdue' => 'integer',
        'periods_overdue' => 'integer',
        'status' => 'integer',
        'schedule_due_date' => 'date',
        'calculated_date' => 'date',
        'waived_at' => 'datetime',
        'paid_at' => 'datetime',
        'calculation_details' => 'array'
    ];

    // Status constants
    const STATUS_PENDING = 0;
    const STATUS_PAID = 1;
    const STATUS_WAIVED = 2;
    const STATUS_CANCELLED = 3;

    /**
     * Get the loan that owns the late fee
     */
    public function loan()
    {
        return $this->belongsTo(PersonalLoan::class, 'loan_id');
    }

    /**
     * Get the schedule that this late fee is for
     */
    public function schedule()
    {
        return $this->belongsTo(LoanSchedule::class, 'schedule_id');
    }

    /**
     * Get the member who owes the late fee
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    /**
     * Get the user who waived the late fee
     */
    public function waivedBy()
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    /**
     * Scope for pending late fees
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for paid late fees
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for waived late fees
     */
    public function scopeWaived($query)
    {
        return $query->where('status', self::STATUS_WAIVED);
    }

    /**
     * Scope for late fees by date range
     */
    public function scopeCalculatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('calculated_date', [$startDate, $endDate]);
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_WAIVED => 'Waived',
            self::STATUS_CANCELLED => 'Cancelled'
        ];

        return $statuses[$this->status] ?? 'Unknown';
    }

    /**
     * Check if late fee is pending
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if late fee is waived
     */
    public function isWaived()
    {
        return $this->status === self::STATUS_WAIVED;
    }

    /**
     * Check if late fee is paid
     */
    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Waive the late fee
     */
    public function waive($reason, $userId = null)
    {
        $this->update([
            'status' => self::STATUS_WAIVED,
            'waiver_reason' => $reason,
            'waived_at' => now(),
            'waived_by' => $userId
        ]);
    }

    /**
     * Mark as paid
     */
    public function markPaid($paymentReference = null)
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'payment_reference' => $paymentReference
        ]);
    }
}
