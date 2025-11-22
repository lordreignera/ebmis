<?php
/**
 * Preview Updated Receipt with Late Fees
 * 
 * Shows how the enhanced receipt will display late fees
 */

echo "=== Updated Receipt Template Preview ===\n\n";

// Simulate Isaac's payment scenario
$scheduleAmount = 5035.00;
$lateFee = 302.10;
$totalPaid = $scheduleAmount + $lateFee;
$daysLate = 10;

echo "Scenario: Isaac pays his second schedule (10 days late)\n";
echo str_repeat('=', 80) . "\n\n";

echo "OLD RECEIPT (Current - No Late Fee Breakdown):\n";
echo str_repeat('-', 80) . "\n";
echo "┌────────────────────────────────────────────────────────────────┐\n";
echo "│  Transaction Details                                           │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
echo "│  Description                              Amount (UGX)         │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
echo "│  Loan Repayment                           5,337                │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
echo "│  Total Paid                               5,337                │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";
echo "\n";
echo "❌ Problem: Member sees 5,337 UGX but doesn't know why it's more than\n";
echo "   the schedule amount of 5,035 UGX\n";
echo "\n\n";

echo "NEW RECEIPT (Enhanced - Shows Late Fee Breakdown):\n";
echo str_repeat('-', 80) . "\n";
echo "┌────────────────────────────────────────────────────────────────┐\n";
echo "│  Payment Breakdown                                             │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
echo "│  Description                              Amount (UGX)         │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
echo "│  Schedule Payment (Principal + Interest)  5,035.00             │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
echo "│  Late Fee (10 days late, 6% per month)     302.10             │\n";
echo "│  Payment made after due date: 12-11-2025                       │\n";
echo "├────────────────────────────────────────────────────────────────┤\n";
echo "│  Total Paid                               5,337.10             │\n";
echo "└────────────────────────────────────────────────────────────────┘\n";
echo "\n";
echo "✅ Benefit: Member clearly sees:\n";
echo "   - Schedule amount: 5,035.00 UGX\n";
echo "   - Late fee: 302.10 UGX (with explanation)\n";
echo "   - Why they're paying more\n";
echo "   - How many days late\n";
echo "\n\n";

echo "FEATURES OF THE ENHANCED RECEIPT:\n";
echo str_repeat('=', 80) . "\n";
echo "1. ✅ Breaks down payment into components\n";
echo "2. ✅ Shows schedule amount (principal + interest) separately\n";
echo "3. ✅ Displays late fee with:\n";
echo "   - Number of days late\n";
echo "   - Late fee rate (6% per period)\n";
echo "   - Period type (days/weeks/months)\n";
echo "4. ✅ Shows original due date\n";
echo "5. ✅ Late fee appears in red color for visibility\n";
echo "6. ✅ Falls back gracefully if no schedule found (shows 'Loan Repayment')\n";
echo "7. ✅ Calculates late fee dynamically based on actual payment date\n";
echo "\n";

echo "HOW IT WORKS:\n";
echo str_repeat('=', 80) . "\n";
echo "1. Receipt loads the schedule associated with the repayment\n";
echo "2. Compares payment date vs. due date\n";
echo "3. If late:\n";
echo "   - Calculates days overdue\n";
echo "   - Converts to periods (days/weeks/months based on loan type)\n";
echo "   - Applies 6% late fee per period\n";
echo "   - Displays breakdown on receipt\n";
echo "4. If on time:\n";
echo "   - Shows schedule amount only\n";
echo "   - No late fee line\n";
echo "\n";

echo "EXAMPLE SCENARIOS:\n";
echo str_repeat('=', 80) . "\n\n";

// Scenario 1: On-time payment
echo "Scenario 1: Payment Made On Time\n";
echo str_repeat('-', 80) . "\n";
echo "Schedule Payment (Principal + Interest)    5,035.00 UGX\n";
echo "──────────────────────────────────────────────────────\n";
echo "Total Paid                                 5,035.00 UGX\n";
echo "\n✓ No late fee shown\n\n";

// Scenario 2: 10 days late (monthly loan)
echo "Scenario 2: 10 Days Late (Monthly Loan)\n";
echo str_repeat('-', 80) . "\n";
echo "Schedule Payment (Principal + Interest)    5,035.00 UGX\n";
echo "Late Fee (10 days late, 6% per month)       302.10 UGX\n";
echo "  Payment made after due date: 12-11-2025\n";
echo "──────────────────────────────────────────────────────\n";
echo "Total Paid                                 5,337.10 UGX\n";
echo "\n⚠️  Late fee = 5,035 × 0.06 × 1 month = 302.10 UGX\n\n";

// Scenario 3: 45 days late (monthly loan)
echo "Scenario 3: 45 Days Late (Monthly Loan)\n";
echo str_repeat('-', 80) . "\n";
$lateFee45 = 5035 * 0.06 * 2; // 2 months
echo "Schedule Payment (Principal + Interest)    5,035.00 UGX\n";
echo "Late Fee (45 days late, 6% per month)       604.20 UGX\n";
echo "  Payment made after due date: 08-10-2025\n";
echo "──────────────────────────────────────────────────────\n";
echo "Total Paid                                 5,639.20 UGX\n";
echo "\n⚠️  Late fee = 5,035 × 0.06 × 2 months = 604.20 UGX\n\n";

// Scenario 4: Weekly loan, 14 days late
echo "Scenario 4: 14 Days Late (Weekly Loan)\n";
echo str_repeat('-', 80) . "\n";
$lateFee14 = 5035 * 0.06 * 2; // 2 weeks
echo "Schedule Payment (Principal + Interest)    5,035.00 UGX\n";
echo "Late Fee (14 days late, 6% per week)        604.20 UGX\n";
echo "  Payment made after due date: 08-11-2025\n";
echo "──────────────────────────────────────────────────────\n";
echo "Total Paid                                 5,639.20 UGX\n";
echo "\n⚠️  Late fee = 5,035 × 0.06 × 2 weeks = 604.20 UGX\n\n";

echo "MEMBER BENEFITS:\n";
echo str_repeat('=', 80) . "\n";
echo "✅ Transparency: Members see exactly what they're paying\n";
echo "✅ Understanding: Clear explanation of late fees\n";
echo "✅ Accountability: Shows due date and how late payment was\n";
echo "✅ Motivation: Encourages on-time payments to avoid fees\n";
echo "✅ Trust: Builds confidence in the system\n";
echo "\n";

echo "SYSTEM BENEFITS:\n";
echo str_repeat('=', 80) . "\n";
echo "✅ Reduced queries: Fewer 'why is my payment more?' questions\n";
echo "✅ Professional: Shows detailed, itemized receipts\n";
echo "✅ Compliance: Clear documentation of charges\n";
echo "✅ Automated: Calculates and displays dynamically\n";
echo "\n";

echo "=== Preview Complete ===\n";
echo "\nThe receipt template has been updated!\n";
echo "Next time a payment is made, it will show the enhanced breakdown.\n";
