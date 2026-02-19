<?php

namespace App\Helpers;

use Carbon\Carbon;

class LoanScheduleHelper
{
    /**
     * Calculate payment date based on period type
     * 
     * BUSINESS RULES:
     * - Weekly loans (type 1): First payment 7 days after disbursement, then every 7 days
     * - Monthly loans (type 2): First payment 30 days after disbursement, then every 30 days
     * - Daily loans (type 3): 7-day grace period, then daily payments (skip Sundays)
     * 
     * @param Carbon $startDate Disbursement date
     * @param int $periodNumber Payment period number (1 = first payment, 2 = second payment, etc.)
     * @param int $periodType 1=Weekly, 2=Monthly, 3=Daily
     * @return Carbon Payment date
     */
    public static function calculatePaymentDate($startDate, int $periodNumber, int $periodType): Carbon
    {
        $date = $startDate instanceof Carbon ? $startDate->copy() : Carbon::parse($startDate);
        
        switch ($periodType) {
            case 1: // Weekly - 7 days after disbursement
                // First payment is 7 days after disbursement
                // Subsequent payments are every 7 days after that
                return $date->addDays(7 * $periodNumber);
                
            case 2: // Monthly - 30 days after disbursement
                // First payment is 30 days after disbursement
                // Subsequent payments are every 30 days after that
                return $date->addDays(30 * $periodNumber);
                
            case 3: // Daily - 7-day grace period, then daily (skip Sundays)
                // Business rule: 7-day grace period after disbursement
                // Then daily payments (NO payments on Sunday)
                // Example: Disbursed Thursday Feb 19 → First payment Thursday Feb 26 (7 days)
                //          Then: Friday Feb 27, Saturday Feb 28, Monday Mar 2 (Sunday skipped), etc.
                
                // Start with 7-day grace period
                $date->addDays(7);
                
                // Add remaining payment days (periodNumber - 1), skipping Sundays
                for ($i = 1; $i < $periodNumber; $i++) {
                    $date->addDay();
                    
                    // Skip Sundays - if we land on Sunday, move to Monday
                    while ($date->isSunday()) {
                        $date->addDay();
                    }
                }
                
                return $date;
                
            default:
                // Fallback for unknown types
                return $date->addDays($periodNumber);
        }
    }
}
