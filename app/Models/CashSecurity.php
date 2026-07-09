<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashSecurity extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 0;
    public const STATUS_PAID = 1;
    public const STATUS_FAILED = 2;

    public const PAYMENT_MOBILE_MONEY = 1;
    public const PAYMENT_CASH = 2;
    public const PAYMENT_BANK_TRANSFER = 3;

    protected $fillable = [
        'member_id',
        'loan_id',
        'amount',
        'payment_type',
        'payment_method',
        'description',
        'pay_ref',
        'status',
        'payment_status',
        'payment_description',
        'payment_raw',
        'payment_phone',
        'transaction_reference',
        'added_by',
        'datecreated',
        'returned',
        'returned_at',
        'return_transaction_reference',
        'return_payment_raw',
        'return_payment_status',
        'returned_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'datecreated' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    /**
     * Get the member that owns the cash security
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the loan that the cash security is for (if any)
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the user who added this cash security
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /**
     * Get payment type name
     */
    public function getPaymentTypeNameAttribute()
    {
        $paymentTypes = [
            self::PAYMENT_MOBILE_MONEY => 'Mobile Money',
            self::PAYMENT_CASH => 'Cash',
            self::PAYMENT_BANK_TRANSFER => 'Bank Transfer',
        ];

        return $paymentTypes[$this->payment_type] ?? 'Unknown';
    }

    public function getStatusLabelAttribute(): string
    {
        if ((int) $this->returned === 1) {
            return 'Returned';
        }

        return match ((int) $this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Failed',
            default => 'Unknown',
        };
    }

    public function getCanEditFinancialsAttribute(): bool
    {
        return (int) $this->status !== self::STATUS_PAID && (int) $this->returned !== 1;
    }

    public function getCanDeleteAttribute(): bool
    {
        return (int) $this->status !== self::STATUS_PAID && (int) $this->returned !== 1;
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute()
    {
        $statusBadges = [
            self::STATUS_PENDING => '<span class="badge bg-warning">Pending</span>',
            self::STATUS_PAID => '<span class="badge bg-success">Paid</span>',
            self::STATUS_FAILED => '<span class="badge bg-danger">Failed</span>',
        ];

        if ((int) $this->returned === 1) {
            return '<span class="badge bg-info">Returned</span>';
        }

        return $statusBadges[$this->status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Scope for paid securities
     */
    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for pending securities
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }
}
