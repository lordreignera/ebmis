<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\PersonalLoan;
use App\Models\GroupLoan;
use App\Models\LoanSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LoanScheduleService
{
    /**
     * Generate loan payment schedule based on loan parameters
     */
    public function generateSchedule($loan): Collection
    {
        // Handle both new unified loans and legacy loans
        $loanData = $this->extractLoanData($loan);
        
        $schedules = collect();
        $principal = (float) $loanData['principal'];
        $rate = (float) $loanData['interest'] / 100; // Convert to decimal
        $period = (int) $loanData['period'];
        $periodType = $loanData['period_type'];
        
        // HALF-TERM INTEREST FORMULA (Universal for ALL loan types)
        // ALL interest is paid in FIRST HALF of loan term
        // SECOND HALF = Principal only (no interest)
        
        // Calculate principal per installment
        $principalInstallment = $principal / $period;
        
        // Calculate half-term (first half of loan)
        $halfTerm = floor($period / 2);
        
        // Edge case: If halfTerm=0 (1 period loan), set to 1
        if ($halfTerm == 0) {
            $halfTerm = 1;
        }
        
        // Determine start date
        $startDate = $this->getStartDate($loanData);
        $currentDate = $startDate;
        $balance = $principal;
        
        // WEEKLY and DAILY loans use REDUCING BALANCE interest calculation
        // MONTHLY loans use FLAT RATE interest calculation
        $useReducingBalance = in_array($periodType, ['0', '1', '3']); // 0=Daily, 1=Weekly, 3=Daily (alternate)
        
        if ($useReducingBalance) {
            // REDUCING BALANCE METHOD (for Weekly/Daily loans)
            // Interest = Global Principal * (Rate * 2)
            // CRITICAL: Global Principal decreases by (Principal / HalfTerm), NOT by principalInstallment
            // This is because interest is calculated on the INTEREST PORTION of the loan,
            // which is fully paid over the first half of the term
            
            $globalPrincipal = $principal;
            $effectiveRate = $rate * 2; // Rate is doubled for reducing balance
            $globalPrincipalReduction = $principal / $halfTerm; // How much global principal decreases per period
            
            for ($count = 1; $count <= $period; $count++) {
                // Calculate next payment date based on period type
                $paymentDate = $this->calculatePaymentDate($currentDate, $periodType, $count);
                
                // Interest only in FIRST HALF of loan term
                if ($count <= $halfTerm) {
                    // Interest = Current Global Principal * Effective Rate
                    $interestAmount = $globalPrincipal * $effectiveRate;
                    
                    // Reduce global principal for NEXT iteration (only in first half)
                    $globalPrincipal -= $globalPrincipalReduction;
                } else {
                    $interestAmount = 0; // SECOND HALF: No interest
                }
                
                $totalPayment = $principalInstallment + $interestAmount;
                $balance -= $principalInstallment;
                
                $schedules->push([
                    'loan_id' => $loanData['id'],
                    'payment_date' => $paymentDate,
                    'payment' => round($totalPayment, 2),
                    'interest' => round($interestAmount, 2),
                    'principal' => round($principalInstallment, 2),
                    'balance' => round(max(0, $balance), 2),
                    'status' => 0,
                    'paid' => 0,
                    'pending_count' => 0,
                    'date_created' => now(),
                    'date_modified' => null,
                    'raw_message' => null,
                    'txn_id' => null,
                    'date_cleared' => null
                ]);
                
                $currentDate = $paymentDate;
            }
            
        } else {
            // FLAT RATE METHOD (for Monthly loans)
            // Total interest distributed equally over first half
            
            $totalInterest = $principal * ($rate * 2); // Monthly rate is doubled
            $interestPerPeriodFirstHalf = $totalInterest / $halfTerm;
            
            for ($count = 1; $count <= $period; $count++) {
                // Calculate next payment date based on period type
                $paymentDate = $this->calculatePaymentDate($currentDate, $periodType, $count);
                
                // Interest only in FIRST HALF of loan term
                if ($count <= $halfTerm) {
                    $interestAmount = $interestPerPeriodFirstHalf;
                } else {
                    $interestAmount = 0; // SECOND HALF: No interest
                }
                
                $totalPayment = $principalInstallment + $interestAmount;
                $balance -= $principalInstallment;
                
                $schedules->push([
                    'loan_id' => $loanData['id'],
                    'payment_date' => $paymentDate,
                    'payment' => round($totalPayment, 2),
                    'interest' => round($interestAmount, 2),
                    'principal' => round($principalInstallment, 2),
                    'balance' => round(max(0, $balance), 2),
                    'status' => 0,
                    'paid' => 0,
                    'pending_count' => 0
                ]);
                
                $currentDate = $paymentDate;
            }
        }
        
        return $schedules;
    }
    
    /**
     * Save generated schedule to database
     */
    public function saveSchedule($loan, Collection $schedules): bool
    {
        try {
            $loanData = $this->extractLoanData($loan);
            
            // Delete existing schedules for this loan
            LoanSchedule::where('loan_id', $loanData['id'])->delete();
            
            // Insert new schedules
            foreach ($schedules as $schedule) {
                LoanSchedule::create($schedule);
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to save loan schedule: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate and save schedule in one step
     */
    public function generateAndSaveSchedule($loan): bool
    {
        $schedules = $this->generateSchedule($loan);
        return $this->saveSchedule($loan, $schedules);
    }
    
    /**
     * Calculate payment date based on period type
     */
    private function calculatePaymentDate(Carbon $currentDate, string $periodType, int $paymentNumber): Carbon
    {
        switch ($periodType) {
            case '1': // Weekly loans - 7 days after disbursement
                if ($paymentNumber == 1) {
                    // First payment: 7 days after disbursement
                    return $currentDate->copy()->addDays(7);
                }
                // Subsequent payments: Add 7 days to previous payment date
                return $currentDate->copy()->addDays(7);
                
            case '2': // Monthly loans - 30 days after disbursement
                if ($paymentNumber == 1) {
                    // First payment: 30 days after disbursement
                    return $currentDate->copy()->addDays(30);
                }
                // Subsequent payments: Add 30 days to previous payment date
                return $currentDate->copy()->addDays(30);
                
            case '3': // Daily loans - 7-day grace period, then daily (skip Sundays)
                if ($paymentNumber == 1) {
                    // First payment: 7 days after disbursement (grace period)
                    return $currentDate->copy()->addDays(7);
                }
                // Subsequent payments: Add 1 day (skipping Sundays) to previous payment date
                return $this->getNextWorkingDay($currentDate);
                
            default:
                return $currentDate->copy()->addDays(30); // Default fallback
        }
    }
    
    /**
     * Get next working day (skip Sundays)
     */
    private function getNextWorkingDay(Carbon $date): Carbon
    {
        $nextDay = $date->copy()->addDay();
        
        // Skip Sundays
        while ($nextDay->isSunday()) {
            $nextDay->addDay();
        }
        
        return $nextDay;
    }
    
    /**
     * Extract loan data from different loan models
     */
    private function extractLoanData($loan): array
    {
        if ($loan instanceof PersonalLoan) {
            $disbursement = $loan->disbursements()
                ->orderBy('created_at', 'asc')
                ->first();

            return [
                'id' => $loan->id,
                'principal' => $loan->principal,
                'interest' => $loan->interest,
                'period' => $loan->period,
                'period_type' => $loan->product ? $loan->product->period_type : '2', // Default to monthly
                'created_at' => $loan->datecreated ?? $loan->created_at,
                'disbursement_date' => $disbursement ? $disbursement->created_at : null
            ];
        } elseif ($loan instanceof GroupLoan) {
            $disbursement = $loan->disbursements()
                ->orderBy('created_at', 'asc')
                ->first();

            return [
                'id' => $loan->id,
                'principal' => $loan->principal,
                'interest' => $loan->interest,
                'period' => $loan->period,
                'period_type' => $loan->product ? $loan->product->period_type : '2',
                'created_at' => $loan->datecreated ?? $loan->created_at,
                'disbursement_date' => $disbursement ? $disbursement->created_at : null
            ];
        } elseif ($loan instanceof Loan) {
            $disbursement = $loan->disbursements()
                ->orderBy('created_at', 'asc')
                ->first();

            return [
                'id' => $loan->id,
                'principal' => $loan->principal,
                'interest' => $loan->interest,
                'period' => $loan->period,
                'period_type' => $loan->product ? $loan->product->period_type : '2',
                'created_at' => $loan->created_at,
                'disbursement_date' => $disbursement ? $disbursement->created_at : null
            ];
        } else {
            throw new \InvalidArgumentException('Invalid loan model provided');
        }
    }
    
    /**
     * Get start date for schedule calculation
     */
    private function getStartDate(array $loanData): Carbon
    {
        if (!empty($loanData['disbursement_date'])) {
            $disbursementDate = $loanData['disbursement_date'];
            if ($disbursementDate instanceof Carbon) {
                return $disbursementDate;
            }
            if (is_string($disbursementDate)) {
                return Carbon::parse($disbursementDate);
            }
        }

        $createdAt = $loanData['created_at'];
        
        if ($createdAt instanceof Carbon) {
            return $createdAt;
        } elseif (is_string($createdAt)) {
            return Carbon::parse($createdAt);
        } else {
            return Carbon::now();
        }
    }
    
    /**
     * Calculate amortization table for display (like legacy system)
     */
    public function calculateAmortizationTable($loan): string
    {
        $schedules = $this->generateSchedule($loan);
        $loanData = $this->extractLoanData($loan);
        
        $html = '<table class="table table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Payment #</th>';
        $html .= '<th>Payment Date</th>';
        $html .= '<th>Payment Amount</th>';
        $html .= '<th>Interest</th>';
        $html .= '<th>Principal</th>';
        $html .= '<th>Balance</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        $totalPayment = 0;
        $totalInterest = 0;
        $totalPrincipal = 0;
        
        foreach ($schedules as $index => $schedule) {
            $html .= '<tr>';
            $html .= '<td>' . ($index + 1) . '</td>';
            $html .= '<td>' . Carbon::parse($schedule['payment_date'])->format('D-d-M-Y') . '</td>';
            $html .= '<td>' . number_format($schedule['payment'], 2) . '</td>';
            $html .= '<td>' . number_format($schedule['interest'], 2) . '</td>';
            $html .= '<td>' . number_format($schedule['principal'], 2) . '</td>';
            $html .= '<td>' . number_format($schedule['balance'], 2) . '</td>';
            $html .= '</tr>';
            
            $totalPayment += $schedule['payment'];
            $totalInterest += $schedule['interest'];
            $totalPrincipal += $schedule['principal'];
        }
        
        // Totals row
        $html .= '<tr class="table-info">';
        $html .= '<td></td>';
        $html .= '<td><strong>AMOUNT TOTALS</strong></td>';
        $html .= '<td><strong>' . number_format($totalPayment, 2) . '</strong></td>';
        $html .= '<td><strong>' . number_format($totalInterest, 2) . '</strong></td>';
        $html .= '<td><strong>' . number_format($totalPrincipal, 2) . '</strong></td>';
        $html .= '<td></td>';
        $html .= '</tr>';
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        return $html;
    }
    
    /**
     * Update schedule payment dates after disbursement
     */
    public function updateScheduleDatesAfterDisbursement($loan, Carbon $disbursementDate): bool
    {
        try {
            $loanData = $this->extractLoanData($loan);
            $periodType = $loanData['period_type'];
            
            $schedules = LoanSchedule::where('loan_id', $loanData['id'])
                                  ->orderBy('id')
                                  ->get();
            
            $currentDate = $disbursementDate;
            
            foreach ($schedules as $index => $schedule) {
                $paymentNumber = $index + 1;
                $paymentDate = $this->calculatePaymentDate($currentDate, $periodType, $paymentNumber);
                
                $schedule->update([
                    'payment_date' => $paymentDate
                ]);
                
                $currentDate = $paymentDate;
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to update schedule dates: ' . $e->getMessage());
            return false;
        }
    }
}