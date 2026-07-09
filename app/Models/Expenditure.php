<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expenditure extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAYMENT_PENDING = 'payment_pending';
    public const STATUS_PAYMENT_FAILED = 'payment_failed';
    public const STATUS_PAID = 'paid';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'expense_number',
        'type',
        'title',
        'description',
        'expense_account_id',
        'payment_account_id',
        'investment_id',
        'branch_id',
        'requested_by',
        'assigned_user_id',
        'amount',
        'expense_date',
        'due_date',
        'status',
        'payment_method',
        'payment_channel',
        'mobile_money_phone',
        'mobile_money_network',
        'mobile_money_reference',
        'mobile_money_status',
        'mobile_money_message',
        'mobile_money_raw',
        'payment_initiated_at',
        'paid_at',
        'approved_by',
        'approved_at',
        'paid_by',
        'journal_entry_id',
        'rollout_batch_id',
        'notes',
        'rejection_reason',
        'receipt_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'payment_initiated_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function expenseAccount()
    {
        return $this->belongsTo(SystemAccount::class, 'expense_account_id', 'Id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(SystemAccount::class, 'payment_account_id', 'Id');
    }

    public function investment()
    {
        return $this->belongsTo(Investment::class, 'investment_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id', 'Id');
    }

    public function rollout()
    {
        return $this->belongsTo(ExpenditureRollout::class, 'rollout_batch_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending approval',
            self::STATUS_APPROVED => 'Approved, pending payment',
            self::STATUS_PAYMENT_PENDING => 'Payment pending',
            self::STATUS_PAYMENT_FAILED => 'Payment failed',
            self::STATUS_PAID => 'Approved and paid',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => ucwords(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBePaid(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_PAYMENT_FAILED], true);
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_PAYMENT_FAILED], true);
    }
}
