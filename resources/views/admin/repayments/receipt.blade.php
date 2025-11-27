<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt - {{ $repayment->txn_id ?? 'N/A' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .company-logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 10px;
        }
        
        .receipt-title {
            font-size: 20px;
            font-weight: bold;
            margin: 15px 0;
        }
        
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .info-section {
            flex: 1;
        }
        
        .info-section h4 {
            color: #2c5aa0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 120px;
        }
        
        .info-value {
            text-align: right;
        }
        
        .amount-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        
        .amount-paid {
            font-size: 36px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }
        
        .payment-details {
            margin: 30px 0;
        }
        
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .details-table th,
        .details-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        .details-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .receipt-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        @media print {
            body {
                font-size: 12px;
            }
            
            .receipt-container {
                padding: 0;
                max-width: none;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-buttons {
            text-align: center;
            margin: 20px 0;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background: #2c5aa0;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #1e3d6f;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Print Buttons -->
        <div class="print-buttons no-print">
            <button class="btn" onclick="window.print()">Print Receipt</button>
            <button class="btn" onclick="window.close()">Close</button>
        </div>

        <!-- Receipt Header -->
        <div class="receipt-header">
            <div class="company-logo">EBIMS - Electronic Banking Information Management System</div>
            <div style="color: #666; margin-bottom: 10px;">Loan Management System</div>
            <div class="receipt-title">PAYMENT RECEIPT</div>
            <div style="font-size: 14px; color: #666;">
                Receipt #{{ $repayment->id }} | Transaction: {{ $repayment->txn_id ?? 'N/A' }}
            </div>
        </div>

        <!-- Receipt Information -->
        <div class="receipt-info">
            <div class="info-section" style="margin-right: 30px;">
                <h4>Payment Information</h4>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value">{{ $repayment->date_created ? \Carbon\Carbon::parse($repayment->date_created)->format('M j, Y g:i A') : 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Method:</span>
                    <span class="info-value">
                        @php
                            echo match($repayment->type) {
                                1 => 'Cash Payment',
                                2 => 'Mobile Money',
                                3 => 'Bank Transfer',
                                default => 'Unknown'
                            };
                        @endphp
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Reference:</span>
                    <span class="info-value">{{ $repayment->transaction_reference ?? $repayment->txn_id ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        @if($repayment->status == 1 || $repayment->payment_status == 'Completed')
                            <span class="status-badge status-confirmed">CONFIRMED</span>
                        @else
                            <span class="status-badge status-pending">PENDING</span>
                        @endif
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Processed By:</span>
                    <span class="info-value">{{ $repayment->addedBy->name ?? 'System' }}</span>
                </div>
            </div>

            <div class="info-section">
                <h4>Member & Loan Details</h4>
                <div class="info-row">
                    <span class="info-label">Member:</span>
                    <span class="info-value">{{ $repayment->loan->member->fname ?? '' }} {{ $repayment->loan->member->lname ?? '' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member ID:</span>
                    <span class="info-value">{{ $repayment->loan->member->code ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Loan Code:</span>
                    <span class="info-value">{{ $repayment->loan->code ?? 'N/A' }}</span>
                </div>
                @if($repayment->loan && $repayment->loan->status != 3)
                    @php
                        // Calculate remaining loan balance
                        $totalLoanAmount = $repayment->loan->principal + $repayment->loan->interest;
                        $totalPaid = \App\Models\Repayment::where('loan_id', $repayment->loan->id)
                            ->where(function($query) {
                                $query->where('status', 1)
                                      ->orWhere('payment_status', 'Completed');
                            })
                            ->sum('amount');
                        $remainingBalance = $totalLoanAmount - $totalPaid;
                    @endphp
                    <div class="info-row">
                        <span class="info-label">Loan Balance:</span>
                        <span class="info-value" style="color: {{ $remainingBalance > 0 ? '#dc3545' : '#28a745' }}; font-weight: bold;">
                            UGX {{ number_format($remainingBalance, 0) }}
                        </span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Amount Paid -->
        <div class="amount-section">
            <div style="font-size: 18px; color: #666; margin-bottom: 10px;">Amount Paid</div>
            <div class="amount-paid">UGX {{ number_format($repayment->amount) }}</div>
            <div style="color: #666; font-style: italic;">
                {{ numberToWords($repayment->amount) }} Shillings Only
            </div>
        </div>

        <!-- Payment Details -->
        <div class="payment-details">
            <h4 style="color: #2c5aa0; margin-bottom: 15px;">Payment Breakdown</h4>
            <table class="details-table">
                <tr>
                    <th>Description</th>
                    <th>Amount (UGX)</th>
                </tr>
                @php
                    // Get the schedule this payment is for
                    $schedule = null;
                    $lateFee = 0;
                    $scheduleAmount = 0;
                    $daysLate = 0;
                    
                    if ($repayment->schedule_id) {
                        $schedule = \App\Models\LoanSchedule::find($repayment->schedule_id);
                        
                        if ($schedule) {
                            $scheduleAmount = $schedule->principal + $schedule->interest;
                            
                            // Calculate late fee if payment was made after due date
                            $paymentDate = $repayment->date_created ? strtotime($repayment->date_created) : time();
                            $dueDate = strtotime($schedule->payment_date);
                            
                            if ($paymentDate > $dueDate) {
                                $datediff = $paymentDate - $dueDate;
                                $daysLate = floor($datediff / (60 * 60 * 24));
                                
                                // Calculate periods overdue
                                $periodsOverdue = 0;
                                $periodType = $repayment->loan->period_type ?? '2';
                                
                                if ($periodType == '1') {
                                    $periodsOverdue = ceil($daysLate / 7);
                                    $periodName = 'week' . ($periodsOverdue > 1 ? 's' : '');
                                } else if ($periodType == '2') {
                                    $periodsOverdue = ceil($daysLate / 30);
                                    $periodName = 'month' . ($periodsOverdue > 1 ? 's' : '');
                                } else if ($periodType == '3') {
                                    $periodsOverdue = $daysLate;
                                    $periodName = 'day' . ($periodsOverdue > 1 ? 's' : '');
                                } else {
                                    $periodsOverdue = ceil($daysLate / 7);
                                    $periodName = 'week' . ($periodsOverdue > 1 ? 's' : '');
                                }
                                
                                // 6% late fee per period
                                $lateFee = ($scheduleAmount * 0.06) * $periodsOverdue;
                            }
                        }
                    }
                @endphp
                
                @if($schedule && $scheduleAmount > 0)
                    <tr>
                        <td>Schedule Payment (Principal + Interest)</td>
                        <td style="text-align: right;">{{ number_format($scheduleAmount, 2) }}</td>
                    </tr>
                    @if($lateFee > 0)
                        <tr style="color: #dc3545;">
                            <td>
                                Late Fee ({{ $daysLate }} days late, 6% per {{ $periodName ?? 'period' }})
                                <br><small style="color: #6c757d;">Payment made after due date: {{ $schedule->payment_date }}</small>
                            </td>
                            <td style="text-align: right;">{{ number_format($lateFee, 2) }}</td>
                        </tr>
                    @endif
                @else
                    <tr>
                        <td>Loan Repayment</td>
                        <td style="text-align: right;">{{ number_format($repayment->amount) }}</td>
                    </tr>
                @endif
                
                @if($repayment->details)
                <tr>
                    <td colspan="2"><strong>Notes:</strong> {{ $repayment->details }}</td>
                </tr>
                @endif
                
                <tr style="background: #f8f9fa; font-weight: bold; font-size: 16px;">
                    <td>Total Paid</td>
                    <td style="text-align: right;">{{ number_format($repayment->amount) }}</td>
                </tr>
            </table>
        </div>

        @if($repayment->loan)
        <!-- Loan Summary -->
        <div class="payment-details">
            <h4 style="color: #2c5aa0; margin-bottom: 15px;">Loan Summary After Payment</h4>
            <table class="details-table">
                <tr>
                    <th>Item</th>
                    <th>Amount (UGX)</th>
                </tr>
                <tr>
                    <td>Original Principal</td>
                    <td style="text-align: right;">{{ number_format($repayment->loan->principal) }}</td>
                </tr>
                <tr>
                    <td>Total Paid to Date</td>
                    <td style="text-align: right;">{{ number_format($repayment->loan->paid) }}</td>
                </tr>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td>Outstanding Balance</td>
                    <td style="text-align: right;">{{ number_format($repayment->loan->outstanding_balance) }}</td>
                </tr>
            </table>
        </div>
        @endif

        <!-- Receipt Footer -->
        <div class="receipt-footer">
            <p><strong>Thank you for your payment!</strong></p>
            <p style="margin: 10px 0;">This is a computer-generated receipt and does not require a signature.</p>
            <p style="color: #666; font-size: 12px;">
                Generated on {{ now()->format('M j, Y g:i A') }} by {{ auth()->user()->name ?? 'System' }}
            </p>
            <p style="color: #666; font-size: 12px; margin-top: 20px;">
                EBIMS - Your trusted partner in financial management<br>
                For inquiries, contact your loan officer or visit our office.
            </p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>