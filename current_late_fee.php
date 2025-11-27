<?php
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
echo "Due Date: 13-11-2025\n\n";

$now = time();
$due = strtotime('13-11-2025');
$diff = $now - $due;
$days = floor($diff / (60 * 60 * 24));
$periods = ceil($days / 30);

echo "Days overdue: $days\n";
echo "Periods overdue (Monthly): $periods\n";
echo "Late Fee Calculation: (650,000 × 0.06) × $periods\n";
echo "Late Fee: " . number_format((650000 * 0.06) * $periods, 0) . " UGX\n";
