<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenditureRollout extends Model
{
    use HasFactory;

    protected $fillable = [
        'rollout_number',
        'title',
        'period_start',
        'period_end',
        'branch_id',
        'expense_account_id',
        'payment_account_id',
        'status',
        'basis',
        'total_amount',
        'generated_by',
        'approved_by',
        'paid_by',
        'approved_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'basis' => 'array',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(ExpenditureRolloutItem::class, 'rollout_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(SystemAccount::class, 'expense_account_id', 'Id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(SystemAccount::class, 'payment_account_id', 'Id');
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
