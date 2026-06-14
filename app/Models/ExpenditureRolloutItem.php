<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenditureRolloutItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'rollout_id',
        'user_id',
        'assigned_loans_count',
        'performing_loans_count',
        'overdue_loans_count',
        'followups_count',
        'collections_amount',
        'payout_amount',
        'notes',
        'expenditure_id',
    ];

    protected $casts = [
        'collections_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
    ];

    public function rollout()
    {
        return $this->belongsTo(ExpenditureRollout::class, 'rollout_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function expenditure()
    {
        return $this->belongsTo(Expenditure::class);
    }
}
