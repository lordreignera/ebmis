<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StaffRemunerationPolicy extends Model
{
    use HasFactory;

    public const CODE = 'weekly_staff_remuneration';

    public const METRICS = [
        'par' => 'PAR',
        'follow_up' => 'Loan follow-ups',
        'portfolio_growth' => 'Portfolio growth',
        'budget_performance' => 'Budget performance',
    ];

    public const DEFAULT_BONUS_BANDS = [
        [null, false, 90, false, 0],
        [90, true, 93, false, 0.75],
        [93, true, 95, false, 1.5],
        [95, true, 98, false, 2.5],
        [98, true, 100, true, 3],
    ];

    public const DEFAULT_PAR_SCORES = [
        ['upper_risk_percent' => 3, 'score' => 100],
        ['upper_risk_percent' => 5, 'score' => 90],
        ['upper_risk_percent' => 8, 'score' => 75],
        ['upper_risk_percent' => 10, 'score' => 60],
        ['upper_risk_percent' => null, 'score' => 0],
    ];

    /** One selection rule for admin and officer previews; never activates a draft. */
    public static function currentForReview(): self
    {
        $policy = static::where('policy_code', self::CODE)->whereIn('status', ['active', 'draft'])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")->latest('id')->first();
        abort_unless($policy, 503, 'Staff remuneration has not been configured yet.');

        return $policy;
    }

    protected $fillable = [
        'policy_code',
        'version',
        'title',
        'status',
        'effective_from',
        'effective_to',
        'base_current_loan_threshold',
        'base_rate_above_threshold',
        'base_rate_at_or_below_threshold',
        'budget_allocation_rate',
        'par_overdue_days',
        'par_bonus_maximum_rate',
        'rounding_scale',
        'timezone',
        'configuration',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'base_current_loan_threshold' => 'decimal:3',
        'base_rate_above_threshold' => 'decimal:3',
        'base_rate_at_or_below_threshold' => 'decimal:3',
        'budget_allocation_rate' => 'decimal:3',
        'par_overdue_days' => 'integer',
        'par_bonus_maximum_rate' => 'decimal:3',
        'configuration' => 'array',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $policy): void {
            if (! $policy->isDirty()) {
                return;
            }
            $configuration = $policy->configuration ?? [];
            $previous = $policy->exists ? $policy->getOriginal() : [];
            if (isset($previous['configuration']) && is_array($previous['configuration'])) {
                unset($previous['configuration']['audit_history']);
            }
            $configuration['audit_history'] = [...data_get($policy->getOriginal('configuration'), 'audit_history', []), [
                'event' => $policy->exists ? 'policy_changed' : 'policy_created',
                'by' => auth()->id(), 'at' => now()->toIso8601String(),
                'changed_fields' => array_keys($policy->getDirty()), 'previous' => $previous,
            ]];
            $policy->configuration = $configuration;
        });
    }

    public function getBonusBandsAttribute(): Collection
    {
        return collect(data_get($this->configuration, 'bonus_bands', []))
            ->map(fn (array $band): object => (object) $band)
            ->values();
    }

    public function calculations()
    {
        return $this->hasMany(StaffRemunerationCalculation::class, 'policy_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
