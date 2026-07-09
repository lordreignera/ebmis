<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!$this->hasRequiredTablesAndColumns()) {
            return;
        }

        $this->repairMissingClearedDates();
        $this->cancelStalePendingRowsOnPaidSchedules();
        $this->repairFailedRowsMarkedCompleted();
    }

    public function down(): void
    {
        // Data repair only; intentionally not reversible.
    }

    private function hasRequiredTablesAndColumns(): bool
    {
        if (!Schema::hasTable('loan_schedules') || !Schema::hasTable('repayments')) {
            return false;
        }

        foreach (['id', 'loan_id', 'status', 'payment', 'paid', 'pending_count', 'date_cleared'] as $column) {
            if (!Schema::hasColumn('loan_schedules', $column)) {
                return false;
            }
        }

        foreach (['schedule_id', 'status', 'amount', 'date_created', 'payment_status', 'pay_status', 'pay_message'] as $column) {
            if (!Schema::hasColumn('repayments', $column)) {
                return false;
            }
        }

        return true;
    }

    private function repairMissingClearedDates(): void
    {
        DB::statement("
            UPDATE loan_schedules ls
            JOIN (
                SELECT schedule_id, MAX(date_created) AS last_successful_payment
                FROM repayments
                WHERE status = 1
                GROUP BY schedule_id
            ) r ON r.schedule_id = ls.id
            SET
                ls.date_cleared = COALESCE(ls.date_cleared, r.last_successful_payment),
                ls.pending_count = 0,
                ls.paid = ls.payment
            WHERE ls.status = 1
              AND ls.paid >= (ls.payment - 1)
              AND r.last_successful_payment IS NOT NULL
              AND (ls.date_cleared IS NULL OR ls.pending_count <> 0 OR ls.paid <> ls.payment)
        ");
    }

    private function cancelStalePendingRowsOnPaidSchedules(): void
    {
        DB::statement("
            UPDATE repayments r
            JOIN loan_schedules ls ON ls.id = r.schedule_id
            SET
                r.status = 2,
                r.payment_status = 'Failed',
                r.pay_status = 'CANCELLED',
                r.pay_message = 'Cancelled by repayment settlement data repair because the schedule was already fully settled',
                ls.pending_count = 0
            WHERE ls.status = 1
              AND r.status = 0
              AND r.amount > 0
              AND (
                    r.payment_status = 'Failed'
                    OR (
                        r.payment_status = 'Pending'
                        AND r.date_created < DATE_SUB(NOW(), INTERVAL 1 DAY)
                    )
              )
        ");
    }

    private function repairFailedRowsMarkedCompleted(): void
    {
        DB::statement("
            UPDATE repayments
            SET payment_status = 'Failed'
            WHERE payment_status = 'Completed'
              AND status IN (-1, 2)
        ");
    }
};
