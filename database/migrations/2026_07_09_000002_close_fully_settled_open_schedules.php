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

        $candidates = DB::table('loan_schedules as ls')
            ->join('personal_loans as pl', 'pl.id', '=', 'ls.loan_id')
            ->leftJoin('products as p', 'p.id', '=', 'pl.product_type')
            ->join(DB::raw("(
                SELECT schedule_id, SUM(amount) AS valid_paid, MAX(date_created) AS last_successful_payment
                FROM repayments
                WHERE status = 1 AND amount > 0
                GROUP BY schedule_id
            ) r"), 'r.schedule_id', '=', 'ls.id')
            ->leftJoin(DB::raw("(
                SELECT schedule_id, status, SUM(amount) AS amount
                FROM late_fees
                WHERE status IN (1, 2)
                GROUP BY schedule_id, status
            ) lf"), 'lf.schedule_id', '=', 'ls.id')
            ->where('ls.status', '<>', 1)
            ->whereRaw('r.valid_paid >= (ls.payment - 1)')
            ->select([
                'ls.id',
                'ls.payment_date',
                'ls.principal',
                'ls.interest',
                'ls.payment',
                'p.period_type',
                'r.valid_paid',
                'r.last_successful_payment',
                DB::raw('SUM(CASE WHEN lf.status = 1 THEN lf.amount ELSE 0 END) AS paid_late_fees'),
                DB::raw('SUM(CASE WHEN lf.status = 2 THEN lf.amount ELSE 0 END) AS waived_late_fees'),
            ])
            ->groupBy([
                'ls.id',
                'ls.payment_date',
                'ls.principal',
                'ls.interest',
                'ls.payment',
                'p.period_type',
                'r.valid_paid',
                'r.last_successful_payment',
            ])
            ->get();

        foreach ($candidates as $schedule) {
            if ($this->isFullySettled($schedule)) {
                DB::table('loan_schedules')
                    ->where('id', $schedule->id)
                    ->update([
                        'paid' => $schedule->payment,
                        'status' => 1,
                        'pending_count' => 0,
                        'date_cleared' => $schedule->last_successful_payment,
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Data repair only; intentionally not reversible.
    }

    private function hasRequiredTablesAndColumns(): bool
    {
        foreach (['loan_schedules', 'personal_loans', 'products', 'repayments', 'late_fees'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        foreach (['id', 'loan_id', 'status', 'payment_date', 'principal', 'interest', 'payment', 'paid', 'pending_count', 'date_cleared'] as $column) {
            if (!Schema::hasColumn('loan_schedules', $column)) {
                return false;
            }
        }

        foreach (['id', 'product_type'] as $column) {
            if (!Schema::hasColumn('personal_loans', $column)) {
                return false;
            }
        }

        foreach (['id', 'period_type'] as $column) {
            if (!Schema::hasColumn('products', $column)) {
                return false;
            }
        }

        foreach (['schedule_id', 'status', 'amount', 'date_created'] as $column) {
            if (!Schema::hasColumn('repayments', $column)) {
                return false;
            }
        }

        foreach (['schedule_id', 'status', 'amount'] as $column) {
            if (!Schema::hasColumn('late_fees', $column)) {
                return false;
            }
        }

        return true;
    }

    private function isFullySettled(object $schedule): bool
    {
        $grossLateFee = $this->grossLateFeeAsOfLastPayment($schedule);
        $waivedLateFee = min($grossLateFee, (float) ($schedule->waived_late_fees ?? 0));
        $paidLateFee = min(
            max(0, $grossLateFee - $waivedLateFee),
            (float) ($schedule->paid_late_fees ?? 0)
        );
        $netLateFee = max(0, $grossLateFee - $waivedLateFee - $paidLateFee);
        $totalDue = (float) $schedule->principal + (float) $schedule->interest + $netLateFee;

        return (float) $schedule->valid_paid >= ($totalDue - 1);
    }

    private function grossLateFeeAsOfLastPayment(object $schedule): float
    {
        $dueTimestamp = $this->parsePaymentDate((string) $schedule->payment_date);
        $lastPaymentTimestamp = strtotime((string) $schedule->last_successful_payment);

        if (!$dueTimestamp || !$lastPaymentTimestamp || $lastPaymentTimestamp <= $dueTimestamp) {
            return 0.0;
        }

        $daysOverdue = max(0, (int) floor(($lastPaymentTimestamp - $dueTimestamp) / 86400));
        $periodType = (string) ($schedule->period_type ?? '1');
        $periodsOverdue = match ($periodType) {
            '1' => (int) ceil($daysOverdue / 7),
            '2' => (int) ceil($daysOverdue / 30),
            default => $daysOverdue,
        };

        return (((float) $schedule->principal + (float) $schedule->interest) * 0.06) * $periodsOverdue;
    }

    private function parsePaymentDate(string $dateString): int|false
    {
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $dateString, $matches)) {
            return mktime(0, 0, 0, (int) $matches[2], (int) $matches[1], (int) $matches[3]);
        }

        return strtotime($dateString);
    }
};
