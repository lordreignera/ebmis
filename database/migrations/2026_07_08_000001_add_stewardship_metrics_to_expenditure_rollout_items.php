<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expenditure_rollout_items')) {
            return;
        }

        Schema::table('expenditure_rollout_items', function (Blueprint $table) {
            $this->decimalColumn($table, 'principal_collected', 'collections_amount');
            $this->decimalColumn($table, 'interest_collected', 'principal_collected');
            $this->decimalColumn($table, 'late_fees_collected', 'interest_collected');
            $this->decimalColumn($table, 'fees_collected', 'late_fees_collected');
            $this->decimalColumn($table, 'qualified_revenue', 'fees_collected');
            $this->decimalColumn($table, 'minimum_wage', 'qualified_revenue', 75000);
            $this->decimalColumn($table, 'overhead_amount', 'minimum_wage', 165000);
            $this->decimalColumn($table, 'net_stewardship_revenue', 'overhead_amount');
            $this->scoreColumn($table, 'collection_score', 'net_stewardship_revenue');
            $this->scoreColumn($table, 'par_score', 'collection_score');
            $this->scoreColumn($table, 'documentation_score', 'par_score');
            $this->scoreColumn($table, 'growth_score', 'documentation_score');
            $this->scoreColumn($table, 'retention_score', 'growth_score');
            $this->scoreColumn($table, 'stewardship_score', 'retention_score');

            if (!Schema::hasColumn('expenditure_rollout_items', 'stewardship_level')) {
                $table->string('stewardship_level', 60)->nullable()->after('stewardship_score');
            }

            $this->scoreColumn($table, 'compensation_rate', 'stewardship_level');
            $this->decimalColumn($table, 'stewardship_compensation', 'compensation_rate');

            if (!Schema::hasColumn('expenditure_rollout_items', 'payment_blocked')) {
                $table->boolean('payment_blocked')->default(false)->after('stewardship_compensation');
            }

            if (!Schema::hasColumn('expenditure_rollout_items', 'block_reason')) {
                $table->text('block_reason')->nullable()->after('payment_blocked');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('expenditure_rollout_items')) {
            return;
        }

        Schema::table('expenditure_rollout_items', function (Blueprint $table) {
            foreach ([
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
            ] as $column) {
                if (Schema::hasColumn('expenditure_rollout_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function decimalColumn(Blueprint $table, string $name, string $after, float $default = 0): void
    {
        if (!Schema::hasColumn('expenditure_rollout_items', $name)) {
            $table->decimal($name, 15, 2)->default($default)->after($after);
        }
    }

    private function scoreColumn(Blueprint $table, string $name, string $after): void
    {
        if (!Schema::hasColumn('expenditure_rollout_items', $name)) {
            $table->decimal($name, 5, 2)->default(0)->after($after);
        }
    }
};
