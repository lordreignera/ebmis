<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for the active-loans list and its KPI aggregation.
 *
 * Without these, /admin/loans/active scans personal_loans / loan_schedules /
 * repayments / late_fees on every request. On production-sized data that pushes
 * the request past the PHP-FPM execution timeout, which the gateway surfaces as
 * a 503. These indexes make the status/branch filters, the ORDER BY datecreated
 * pagination, and the schedule/payment aggregations index-driven.
 *
 * The migration is idempotent: each index is only added when the table and all
 * of its columns exist and the index is not already present.
 */
return new class extends Migration
{
    /**
     * [table, index name, [columns]]
     */
    private function indexDefinitions(): array
    {
        return [
            ['personal_loans',       'pl_status_datecreated_idx', ['status', 'datecreated']],
            ['personal_loans',       'pl_branch_status_idx',      ['branch_id', 'status']],
            ['personal_loans',       'pl_member_idx',             ['member_id']],
            ['group_loans',          'gl_status_datecreated_idx', ['status', 'datecreated']],
            ['group_loans',          'gl_branch_status_idx',      ['branch_id', 'status']],
            ['loan_schedules',       'ls_loan_status_idx',        ['loan_id', 'status']],
            ['group_loan_schedules', 'gls_loan_status_idx',       ['loan_id', 'status']],
            ['repayments',           'rp_schedule_status_idx',    ['schedule_id', 'status']],
            ['group_repayments',     'grp_schedule_status_idx',   ['schedule_id', 'status']],
            ['late_fees',            'lf_schedule_status_idx',    ['schedule_id', 'status']],
            ['late_fees',            'lf_loan_status_idx',        ['loan_id', 'status']],
        ];
    }

    public function up(): void
    {
        foreach ($this->indexDefinitions() as [$table, $name, $columns]) {
            $this->addIndexIfMissing($table, $name, $columns);
        }
    }

    public function down(): void
    {
        foreach ($this->indexDefinitions() as [$table, $name, $columns]) {
            $this->dropIndexIfExists($table, $name);
        }
    }

    private function addIndexIfMissing(string $table, string $name, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return; // Skip on environments where a column is absent.
            }
        }

        // Skip when this exact index, or an equivalent one (same leading
        // columns under any name), already exists. Many environments already
        // carry these indexes under different names from earlier tuning scripts.
        if ($this->indexExists($table, $name) || $this->hasEquivalentIndex($table, $columns)) {
            return;
        }

        $columnList = implode('`, `', $columns);

        try {
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` (`{$columnList}`)");
        } catch (\Throwable $e) {
            // Ignore: equivalent index may already exist under a different name,
            // or online DDL is not permitted on this connection.
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $name)) {
            return;
        }

        try {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
        } catch (\Throwable $e) {
            // Ignore.
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name]));
    }

    /**
     * True when an index already exists whose leading columns match the given
     * columns in the same order (regardless of the index name).
     */
    private function hasEquivalentIndex(string $table, array $columns): bool
    {
        $byName = [];

        foreach (DB::select("SHOW INDEX FROM `{$table}`") as $row) {
            $byName[$row->Key_name][(int) $row->Seq_in_index] = $row->Column_name;
        }

        foreach ($byName as $sequence) {
            ksort($sequence);
            $leading = array_slice(array_values($sequence), 0, count($columns));

            if ($leading === $columns) {
                return true;
            }
        }

        return false;
    }
};
