<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expenditure extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_number',
        'type',
        'title',
        'description',
        'expense_account_id',
        'payment_account_id',
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
}
