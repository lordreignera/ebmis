<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_remuneration_weekly_calculations', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('policy_id')->constrained('staff_remuneration_policies')->restrictOnDelete();
            $table->string('policy_version', 40);
            $table->foreignId('officer_id')->constrained('users')->restrictOnDelete();
            // Link here so installation never needs an ALTER on expenditures.
            $table->foreignId('expenditure_id')->nullable()->unique()->constrained('expenditures')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 20)->default('review');

            foreach (['interest_revenue', 'administration_fee_revenue', 'registration_fee_revenue', 'late_fee_revenue', 'restructuring_fee_revenue', 'other_qualified_revenue', 'qualified_revenue'] as $column) {
                $table->decimal($column, 15, 2)->default(0);
            }
            $table->unsignedInteger('total_active_loans')->default(0);
            $table->unsignedInteger('current_active_loans')->default(0);
            $table->decimal('current_loan_score', 6, 3)->nullable();
            $table->decimal('base_rate', 6, 3)->nullable();
            $table->decimal('base_amount', 15, 2)->nullable();
            $table->decimal('par_risk_principal', 15, 2)->default(0);
            $table->decimal('par_total_outstanding_principal', 15, 2)->default(0);
            $table->unsignedInteger('par_overdue_days')->default(120);
            $table->decimal('par_bonus_maximum_rate', 6, 3)->default(3);
            $table->decimal('par_percent', 6, 3)->nullable();
            $table->decimal('par_score', 6, 3)->nullable();
            $table->unsignedInteger('follow_up_due_count')->default(0);
            $table->unsignedInteger('follow_up_completed_count')->default(0);
            $table->decimal('follow_up_score', 6, 3)->nullable();
            $table->unsignedInteger('opening_active_clients')->default(0);
            $table->unsignedInteger('closing_active_clients')->default(0);
            $table->decimal('growth_target', 15, 2)->nullable();
            $table->decimal('growth_score', 6, 3)->nullable();
            foreach (['previous_week_qualified_revenue', 'allocated_budget', 'approved_budget_expenses', 'budget_overrun'] as $column) {
                $table->decimal($column, 15, 2)->default(0);
            }
            $table->decimal('budget_score', 6, 3)->nullable();
            foreach (['budget_bonus_rate', 'par_bonus_rate', 'follow_up_bonus_rate', 'growth_bonus_rate', 'total_bonus_rate'] as $column) {
                $table->decimal($column, 6, 3)->default(0);
            }
            // NULL means unresolved, not an earned zero.
            foreach (['bonus_amount', 'gross_remuneration', 'current_bonus_offset', 'net_remuneration', 'new_deficit_amount'] as $column) {
                $table->decimal($column, 15, 2)->nullable();
            }
            $table->decimal('deficit_recovery_amount', 15, 2)->default(0);
            foreach (['policy_snapshot', 'qualified_revenue_snapshot', 'input_snapshot', 'bonus_breakdown', 'budget_snapshot', 'deficit_snapshot', 'audit_history'] as $column) {
                $table->json($column)->nullable();
            }
            $table->text('calculation_notes')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            foreach (['reviewed', 'approved', 'paid'] as $event) {
                $table->foreignId($event.'_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp($event.'_at')->nullable();
            }
            $table->timestamps();
            // One officer/week, including when a new policy version is created.
            $table->unique(['officer_id', 'period_start', 'period_end'], 'staff_rem_calc_period_unique');
            $table->index(['status', 'period_start', 'period_end'], 'staff_rem_calc_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_remuneration_weekly_calculations');
    }
};
