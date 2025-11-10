<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisbursementTransaction extends Model
{
    protected $table = 'disbursement_txn';

    protected $fillable = [
        'disbursement_id',
        'txn_reference',
        'network',
        'phone',
        'account_number',
        'status',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];

    /**
     * Get the disbursement this transaction belongs to
     */
    public function disbursement(): BelongsTo
    {
        return $this->belongsTo(Disbursement::class);
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
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', '00');
    }

    /**
     * Scope for successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', '01');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', '02');
    }

    /**
     * Scope for mobile money transactions
     */
    public function scopeMobileMoney($query)
    {
        return $query->whereIn('network', ['MTN', 'AIRTEL']);
    }

    /**
     * Scope for cheque transactions
     */
    public function scopeCheque($query)
    {
        return $query->where('network', 'CHEQUE');
    }
}