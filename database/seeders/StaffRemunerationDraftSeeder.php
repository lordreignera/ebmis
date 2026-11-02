<?php

namespace Database\Seeders;

use App\Models\StaffRemunerationPolicy;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class StaffRemunerationDraftSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::findOrCreate('view-own-remuneration', 'web');
        Role::where('guard_name', 'web')->whereIn('name', [
            'Branch Manager', 'Loan Officer', 'Field Officer', 'Credit Officer', 'Collections Officer',
        ])->get()->each(function (Role $role) use ($permission): void {
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        });

        $policy = StaffRemunerationPolicy::firstOrCreate(
            [
                'policy_code' => StaffRemunerationPolicy::CODE,
                'version' => '2026-10-26-client-clarified',
            ],
            [
                'title' => 'E-BIMS Weekly Staff Remuneration Policy - Draft aligned to client decisions',
                'status' => 'draft',
                'effective_from' => null,
                'effective_to' => null,
                'base_current_loan_threshold' => 90,
                'base_rate_above_threshold' => 12,
                'base_rate_at_or_below_threshold' => 5,
                'budget_allocation_rate' => 10,
                'par_overdue_days' => 120,
                'par_bonus_maximum_rate' => 3,
                'rounding_scale' => 2,
                'timezone' => 'Africa/Kampala',
                'configuration' => [
                    'payment_cycle' => 'weekly_calendar_period',
                    'working_day_or_holiday_proration' => false,
                    'par_threshold_basis' => 'calendar_days_after_oldest_unpaid_instalment',
                    'follow_up_rule' => 'distinct_recorded_followups_on_active_assigned_loans_divided_by_active_assigned_loans',
                    'bonus_metrics' => array_keys(StaffRemunerationPolicy::METRICS),
                    'par_score_mapping' => StaffRemunerationPolicy::DEFAULT_PAR_SCORES,
                    'follow_up_score_mapping' => null,
                    'follow_up_schedule' => null,
                    'weekly_growth_target' => 1,
                    'budget_score_mapping' => null,
                    'operating_budget_score_method' => 'inverse_utilization_capped',
                    'budget_overrun_offset' => 'gross_remuneration',
                    'weekly_expense_report_required' => true,
                    'uncovered_deficit_recovery' => 'subsequent_payouts',
                    'first_week_budget_source' => 'manual_expense',
                    'fund_allocation' => [
                        'enabled' => true,
                        'revolving_rate' => 45,
                        'growth_rate' => 19,
                        'bonus_budget_rate' => 14,
                        'accounts' => [],
                    ],
                    'source_documents' => [
                        'workbook' => 'E-BIMS Staff Remuneration Calculator Clarified.xlsx: Assumptions B13:B17 and Portfolio Growth Standard',
                        'pdf' => 'EBIMS_Staff_Remuneration_Client_Validation (2).pdf: pages 1-4, 9',
                        'client_amendments' => '26 October 2026: four bonuses capped at 12%; 14% allocation budget; gross overrun deduction; 120 calendar-day PAR; one net new client weekly.',
                    ],
                ],
            ]
        );

        // Re-running the seeder must never reset an administrator's policy edits.
        if (! $policy->wasRecentlyCreated) {
            return;
        }

        $bands = StaffRemunerationPolicy::DEFAULT_BONUS_BANDS;

        $configuration = $policy->configuration ?? [];
        $configuration['bonus_bands'] = [];

        foreach (array_keys(StaffRemunerationPolicy::METRICS) as $metricCode) {
            foreach ($bands as $sortOrder => [$lower, $lowerInclusive, $upper, $upperInclusive, $earnedRate]) {
                $configuration['bonus_bands'][] = [
                    'metric_code' => $metricCode,
                    'lower_score' => $lower,
                    'lower_inclusive' => $lowerInclusive,
                    'upper_score' => $upper,
                    'upper_inclusive' => $upperInclusive,
                    'earned_rate' => $earnedRate,
                    'maximum_rate' => 3,
                    'sort_order' => $sortOrder,
                    'notes' => 'Draft band; score source and metric-specific mapping remain configurable.',
                ];
            }
        }

        $policy->update(['configuration' => $configuration]);
    }
}
