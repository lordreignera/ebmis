<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffRemunerationCalculation extends Model
{
    use HasFactory;

    protected $table = 'staff_remuneration_weekly_calculations';

    public const STATUS_REVIEW = 'review';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAYMENT_PENDING = 'payment_pending';

    public const STATUS_PAYMENT_FAILED = 'payment_failed';

    public const STATUS_PAID = 'paid';

    public const STATUS_SETTLED = 'settled';

    public function scopeForOfficerPeriod(Builder $query, int $officerId, mixed $start, mixed $end): Builder
    {
        return $query->where('officer_id', $officerId)->whereDate('period_start', $start)->whereDate('period_end', $end);
    }

    public function isReviewable(): bool
    {
        return $this->status === self::STATUS_REVIEW && ! $this->expenditure_id
            && ! data_get($this->input_snapshot, 'fund_posting.journal_id');
    }

    protected $fillable = [
        'policy_id',
        'policy_version',
        'officer_id',
        'expenditure_id',
        'period_start',
        'period_end',
        'status',
        'interest_revenue',
        'administration_fee_revenue',
        'registration_fee_revenue',
        'late_fee_revenue',
        'restructuring_fee_revenue',
        'other_qualified_revenue',
        'qualified_revenue',
        'total_active_loans',
        'current_active_loans',
        'current_loan_score',
        'base_rate',
        'base_amount',
        'par_risk_principal',
        'par_total_outstanding_principal',
        'par_overdue_days',
        'par_bonus_maximum_rate',
        'par_percent',
        'par_score',
        'follow_up_due_count',
        'follow_up_completed_count',
        'follow_up_score',
        'opening_active_clients',
        'closing_active_clients',
        'growth_target',
        'growth_score',
        'previous_week_qualified_revenue',
        'allocated_budget',
        'approved_budget_expenses',
        'budget_overrun',
        'budget_score',
        'budget_bonus_rate',
        'par_bonus_rate',
        'follow_up_bonus_rate',
        'growth_bonus_rate',
        'total_bonus_rate',
        'bonus_amount',
        'gross_remuneration',
        'current_bonus_offset',
        'deficit_recovery_amount',
        'net_remuneration',
        'new_deficit_amount',
        'policy_snapshot',
        'qualified_revenue_snapshot',
        'input_snapshot',
        'bonus_breakdown',
        'budget_snapshot',
        'deficit_snapshot',
        'audit_history',
        'calculation_notes',
        'generated_by',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'policy_snapshot' => 'array',
        'interest_revenue' => 'decimal:2',
        'administration_fee_revenue' => 'decimal:2',
        'registration_fee_revenue' => 'decimal:2',
        'late_fee_revenue' => 'decimal:2',
        'restructuring_fee_revenue' => 'decimal:2',
        'other_qualified_revenue' => 'decimal:2',
        'qualified_revenue' => 'decimal:2',
        'current_loan_score' => 'decimal:3',
        'base_rate' => 'decimal:3',
        'base_amount' => 'decimal:2',
        'par_risk_principal' => 'decimal:2',
        'par_total_outstanding_principal' => 'decimal:2',
        'par_overdue_days' => 'integer',
        'par_bonus_maximum_rate' => 'decimal:3',
        'par_percent' => 'decimal:3',
        'par_score' => 'decimal:3',
        'follow_up_score' => 'decimal:3',
        'growth_target' => 'decimal:2',
        'growth_score' => 'decimal:3',
        'previous_week_qualified_revenue' => 'decimal:2',
        'allocated_budget' => 'decimal:2',
        'approved_budget_expenses' => 'decimal:2',
        'budget_overrun' => 'decimal:2',
        'budget_score' => 'decimal:3',
        'budget_bonus_rate' => 'decimal:3',
        'par_bonus_rate' => 'decimal:3',
        'follow_up_bonus_rate' => 'decimal:3',
        'growth_bonus_rate' => 'decimal:3',
        'total_bonus_rate' => 'decimal:3',
        'bonus_amount' => 'decimal:2',
        'gross_remuneration' => 'decimal:2',
        'current_bonus_offset' => 'decimal:2',
        'deficit_recovery_amount' => 'decimal:2',
        'net_remuneration' => 'decimal:2',
        'new_deficit_amount' => 'decimal:2',
        'qualified_revenue_snapshot' => 'array',
        'input_snapshot' => 'array',
        'bonus_breakdown' => 'array',
        'budget_snapshot' => 'array',
        'deficit_snapshot' => 'array',
        'audit_history' => 'array',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function policy()
    {
        return $this->belongsTo(StaffRemunerationPolicy::class, 'policy_id');
    }

    public function officer()
    {
        return $this->belongsTo(User::class, 'officer_id');
    }

    public function expenditure()
    {
        return $this->belongsTo(Expenditure::class, 'expenditure_id');
    }
}
