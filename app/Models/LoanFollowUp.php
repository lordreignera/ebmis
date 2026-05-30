<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_type',
        'loan_id',
        'member_id',
        'branch_id',
        'assigned_to',
        'created_by',
        'follow_up_at',
        'contact_method',
        'outcome',
        'willing_to_pay',
        'promise_date',
        'promise_amount',
        'next_action',
        'next_follow_up_date',
        'sms_sent',
        'sms_message',
        'notes',
    ];

    protected $casts = [
        'follow_up_at' => 'datetime',
        'promise_date' => 'date',
        'next_follow_up_date' => 'date',
        'promise_amount' => 'decimal:2',
        'willing_to_pay' => 'boolean',
        'sms_sent' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedOfficer()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
