<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashSecurity extends Model
{
    use HasFactory;

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

    /**
     * Get payment type name
     */
    public function getPaymentTypeNameAttribute()
    {
        $paymentTypes = [
            1 => 'Mobile Money',
            2 => 'Cash',
            3 => 'Bank Transfer'
        ];

        return $paymentTypes[$this->payment_type] ?? 'Unknown';
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute()
    {
        $statusBadges = [
            0 => '<span class="badge bg-warning">Pending</span>',
            1 => '<span class="badge bg-success">Paid</span>',
            2 => '<span class="badge bg-danger">Failed</span>',
        ];

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
