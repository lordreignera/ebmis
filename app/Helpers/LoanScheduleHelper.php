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
     * - Monthly loans (type 2): First payment same day next month, then monthly on same day
     * - Daily loans (type 3): First payment after 1 day release (skip Sundays), then daily (skip Sundays)
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
                
            case 2: // Monthly - same day next month
                // First payment is same day next month
                // Subsequent payments are monthly on same day
                return $date->addMonths($periodNumber);
                
            case 3: // Daily - skip 1 day (release) then daily payments (skip Sundays only)
                // Business rule: Skip 1 day after disbursement (client release day)
                // Then subsequent payments are daily, NO payments on Sunday
                // Example: Disbursed Friday → Skip Saturday (release) → Skip Sunday → First payment Monday
                // Example: Disbursed Monday → Skip Tuesday (release) → First payment Wednesday
                
                // First skip 1 day (release day)
                $date->addDay();
                
                // Add payment days, skipping Sundays only
                for ($i = 0; $i < $periodNumber; $i++) {
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
