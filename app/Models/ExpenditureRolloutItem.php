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
        'principal_collected',
        'interest_collected',
        'late_fees_collected',
        'fees_collected',
        'qualified_revenue',
        'minimum_wage',
        'overhead_amount',
        'net_stewardship_revenue',
        'collection_score',
        'par_score',
        'documentation_score',
        'growth_score',
        'retention_score',
        'stewardship_score',
        'stewardship_level',
        'compensation_rate',
        'stewardship_compensation',
        'payment_blocked',
        'block_reason',
        'payout_amount',
        'notes',
        'expenditure_id',
    ];

    protected $casts = [
        'collections_amount' => 'decimal:2',
        'principal_collected' => 'decimal:2',
        'interest_collected' => 'decimal:2',
        'late_fees_collected' => 'decimal:2',
        'fees_collected' => 'decimal:2',
        'qualified_revenue' => 'decimal:2',
        'minimum_wage' => 'decimal:2',
        'overhead_amount' => 'decimal:2',
        'net_stewardship_revenue' => 'decimal:2',
        'collection_score' => 'decimal:2',
        'par_score' => 'decimal:2',
        'documentation_score' => 'decimal:2',
        'growth_score' => 'decimal:2',
        'retention_score' => 'decimal:2',
        'stewardship_score' => 'decimal:2',
        'compensation_rate' => 'decimal:2',
        'stewardship_compensation' => 'decimal:2',
        'payment_blocked' => 'boolean',
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
