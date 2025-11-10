<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawPayment extends Model
{
    protected $fillable = [
        'txn_id',
        'amount',
        'phone_number',
        'status',
        'type',
        'loan_id',
        'disbursement_id',
        'schedule_id',
        'member_id',
        'response_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'response_data' => 'array',
    ];

    /**
     * Get the loan this payment relates to
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the disbursement this payment relates to
     */
    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(Disbursement::class);
    }

    /**
     * Get the member this payment relates to
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the loan schedule this payment relates to
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(LoanSchedule::class, 'schedule_id');
    }

    /**
     * Get status name
     */
    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case '00': return 'Pending';
            case '01': return 'Successful';
            case '02': return 'Failed';
            default: return 'Unknown';
        }
    }

    /**
     * Get type name
     */
    public function getTypeNameAttribute()
    {
        switch ($this->type) {
            case 'disbursement': return 'Disbursement';
            case 'collection': return 'Repayment Collection';
            case 'repayment': return 'Repayment';
            default: return 'Unknown';
        }
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute()
    {
        switch ($this->status) {
            case '00': return 'badge-warning';
            case '01': return 'badge-success';
            case '02': return 'badge-danger';
            default: return 'badge-secondary';
        }
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', '00');
    }

    /**
     * Scope for successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', '01');
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', '02');
    }

    /**
     * Scope for disbursement payments
     */
    public function scopeDisbursements($query)
    {
        return $query->where('type', 'disbursement');
    }

    /**
     * Scope for collection payments
     */
    public function scopeCollections($query)
    {
        return $query->where('type', 'collection');
    }

    /**
     * Scope for repayment payments
     */
    public function scopeRepayments($query)
    {
        return $query->where('type', 'repayment');
    }
}