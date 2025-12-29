<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class LoanClosureService
{
    private float $tolerance;

    public function __construct(float $tolerance = 0.5)
    {
        $this->tolerance = $tolerance;
    }

    public function closeLoan(int $loanId, ?int $actorId = null): array
    {
        DB::beginTransaction();

        try {
            $loan = $this->fetchLoan($loanId);

            if (!$loan) {
                throw new RuntimeException("Loan {$loanId} not found");
            }

            if ((int) ($loan->status ?? 0) === 3) {
                DB::rollBack();

                return [
                    'success' => true,
                    'message' => 'Loan already closed',
                    'loan_id' => $loanId,
                    'already_closed' => true,
                    'closed_timestamp' => $loan->date_closed ?? null,
                ];
            }

            $schedules = DB::table('loan_schedules')
                ->where('loan_id', $loanId)
                ->orderBy('payment_date')
                ->lockForUpdate()
                ->get();

            if ($schedules->isEmpty()) {
                throw new RuntimeException('Loan schedules missing; aborting to avoid inconsistent state.');
            }

            $analysis = $this->analyzeSchedules($schedules);

            if ($analysis['outstanding'] > $this->tolerance) {
                throw new RuntimeException(
                    'Loan has outstanding balance of UGX ' . number_format($analysis['outstanding'], 2)
                );
            }

            $totals = $this->fetchRepaymentTotals($loanId);

            if ($analysis['schedules_needing_adjustment']) {
                DB::table('loan_schedules')
                    ->whereIn('id', $analysis['schedules_needing_adjustment'])
                    ->update(['paid' => DB::raw('payment')]);
            }

            $now = Carbon::now();

            $updatedSchedules = DB::table('loan_schedules')
                ->where('loan_id', $loanId)
                ->where(function ($query) {
                    $query->whereColumn('paid', '<', 'payment')
                          ->orWhereNull('status')
                          ->orWhere('status', 0);
                })
                ->update([
                    'status' => 1,
                    'pending_count' => 0,
                    'date_cleared' => $now,
                ]);

            $updatedRepayments = DB::table('repayments')
                ->where('loan_id', $loanId)
                ->where('status', 1)
                ->where(function ($query) {
                    $query->whereNull('payment_status')
                          ->orWhere('payment_status', '!=', 'Paid');
                })
                ->update([
                    'payment_status' => 'Paid',
                ]);

            $updatedLateFees = DB::table('late_fees')
                ->where('loan_id', $loanId)
                ->where(function ($query) {
                    $query->whereNull('status')
                          ->orWhereNotIn('status', [2]);
                })
                ->update([
                    'status' => 2,
                    'updated_at' => $now,
                ]);

            DB::table('personal_loans')
                ->where('id', $loanId)
                ->update([
                    'status' => 3,
                    'date_closed' => $now,
                    'updated_at' => $now,
                ]);

            DB::commit();

            Log::info('Loan closed via LoanClosureService', [
                'loan_id' => $loanId,
                'actor_id' => $actorId,
                'scheduled_total' => $analysis['total_scheduled'],
                'total_paid' => $totals['total_paid'],
                'outstanding' => $analysis['outstanding'],
                'overpayment' => $analysis['overpayment'],
            ]);

            return [
                'success' => true,
                'message' => 'Loan closed successfully',
                'loan_id' => $loanId,
                'scheduled_total' => $analysis['total_scheduled'],
                'total_paid' => $totals['total_paid'],
                'outstanding' => $analysis['outstanding'],
                'overpayment' => $analysis['overpayment'],
                'repayments_updated' => $updatedRepayments,
                'schedules_updated' => $updatedSchedules,
                'late_fees_updated' => $updatedLateFees,
                'closed_timestamp' => $now->toDateTimeString(),
                'already_closed' => false,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Loan closure failed', [
                'loan_id' => $loanId,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function fetchLoan(int $loanId): ?object
    {
        $loan = DB::table('personal_loans')->where('id', $loanId)->lockForUpdate()->first();

        if ($loan) {
            return $loan;
        }

        return DB::table('loans')->where('id', $loanId)->lockForUpdate()->first();
    }

    private function analyzeSchedules($schedules): array
    {
        $outstanding = 0.0;
        $overpayment = 0.0;
        $scheduleIdsNeedingAdjustment = [];

        foreach ($schedules as $schedule) {
            $diff = (float) $schedule->payment - (float) $schedule->paid;

            if ($diff > $this->tolerance) {
                $outstanding += $diff;
            } elseif ($diff < -$this->tolerance) {
                $overpayment += abs($diff);
            } elseif ($diff > 0.0 && $diff <= $this->tolerance) {
                $scheduleIdsNeedingAdjustment[] = $schedule->id;
            }
        }

        return [
            'total_scheduled' => (float) $schedules->sum('payment'),
            'outstanding' => $outstanding,
            'overpayment' => $overpayment,
            'schedules_needing_adjustment' => $scheduleIdsNeedingAdjustment,
        ];
    }

    private function fetchRepaymentTotals(int $loanId): array
    {
        $totalPaid = (float) DB::table('repayments')
            ->where('loan_id', $loanId)
            ->where('status', 1)
            ->sum('amount');

        return [
            'total_paid' => $totalPaid,
        ];
    }
}
