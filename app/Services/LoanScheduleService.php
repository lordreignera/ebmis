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
        
        // Calculate total interest based on loan type
        if ($periodType == '2') {
            // MONTHLY loans: Interest rate is DOUBLED
            $totalInterest = $principal * ($rate * 2);
        } else {
            // WEEKLY and DAILY loans: Interest rate as-is
            $totalInterest = $principal * $rate;
        }
        
        // Distribute interest equally over FIRST HALF of term
        // Edge case: If halfTerm=0 (1 period loan), all interest in first payment
        if ($halfTerm == 0) {
            $interestPerPeriodFirstHalf = $totalInterest;
            $halfTerm = 1; // Set to 1 so first payment gets all interest
        } else {
            $interestPerPeriodFirstHalf = $totalInterest / $halfTerm;
        }
        
        // Determine start date
        $startDate = $this->getStartDate($loanData);
        $currentDate = $startDate;
        $balance = $principal;
        
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
                'payment_number' => $count,
                'payment_date' => $paymentDate,
                'payment' => round($totalPayment, 2),
                'interest' => round($interestAmount, 2),
                'principal' => round($principalInstallment, 2),
                'balance' => round(max(0, $balance), 2),
                'status' => 0, // 0 = unpaid, 1 = paid
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $currentDate = $paymentDate;
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
        if ($paymentNumber == 1) {
            // For first payment, calculate from current date
            $baseDate = $currentDate;
        } else {
            $baseDate = $currentDate;
        }
        
        switch ($periodType) {
            case '1': // Weekly loans - next Friday
                if ($paymentNumber == 1) {
                    return $this->getNextFriday($baseDate);
                }
                return $this->getNextFriday($baseDate);
                
            case '2': // Monthly loans - 25th of next month
                if ($paymentNumber == 1) {
                    return $this->getNext25th($baseDate);
                }
                return $baseDate->copy()->addMonth();
                
            case '3': // Daily loans - next working day (skip Sundays)
                if ($paymentNumber == 1) {
                    return $this->getNextWorkingDay($baseDate);
                }
                return $this->getNextWorkingDay($baseDate);
                
            default:
                return $baseDate->copy()->addDays(30); // Default fallback
        }
    }
    
    /**
     * Get next Friday from given date
     */
    private function getNextFriday(Carbon $date): Carbon
    {
        $nextFriday = $date->copy();
        
        // If today is Friday, get next Friday
        if ($nextFriday->isFriday()) {
            $nextFriday->addWeek();
        } else {
            // Get next Friday
            $nextFriday->next(Carbon::FRIDAY);
        }
        
        return $nextFriday;
    }
    
    /**
     * Get 25th of current or next month
     */
    private function getNext25th(Carbon $date): Carbon
    {
        $day = $date->day;
        
        if ($day < 25) {
            // Same month, 25th
            return $date->copy()->day(25);
        } else {
            // Next month, 25th
            return $date->copy()->addMonth()->day(25);
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
            return [
                'id' => $loan->id,
                'principal' => $loan->principal,
                'interest' => $loan->interest,
                'period' => $loan->period,
                'period_type' => $loan->product ? $loan->product->period_type : '2', // Default to monthly
                'created_at' => $loan->datecreated ?? $loan->created_at
            ];
        } elseif ($loan instanceof GroupLoan) {
            return [
                'id' => $loan->id,
                'principal' => $loan->principal,
                'interest' => $loan->interest,
                'period' => $loan->period,
                'period_type' => $loan->product ? $loan->product->period_type : '2',
                'created_at' => $loan->datecreated ?? $loan->created_at
            ];
        } elseif ($loan instanceof Loan) {
            return [
                'id' => $loan->id,
                'principal' => $loan->principal,
                'interest' => $loan->interest,
                'period' => $loan->period,
                'period_type' => $loan->product ? $loan->product->period_type : '2',
                'created_at' => $loan->created_at
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
        
        foreach ($schedules as $schedule) {
            $html .= '<tr>';
            $html .= '<td>' . $schedule['payment_number'] . '</td>';
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
                                  ->orderBy('payment_number')
                                  ->get();
            
            $currentDate = $disbursementDate;
            
            foreach ($schedules as $schedule) {
                $paymentDate = $this->calculatePaymentDate($currentDate, $periodType, $schedule->payment_number);
                
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