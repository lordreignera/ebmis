<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupRepayment extends Model
{
    use HasFactory;

    protected $table = 'group_repayments';

    protected $fillable = [
        'type',
        'details',
        'loan_id',
        'schedule_id',
        'amount',
        'added_by',
        'status',
        'payment_status',
        'transaction_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function loan()
    {
        return $this->belongsTo(GroupLoan::class, 'loan_id');
    }

    public function schedule()
    {
        return $this->belongsTo(GroupLoanSchedule::class, 'schedule_id');
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
