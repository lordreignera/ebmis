<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Repayment Schedule - {{ $loan->code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #666;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th {
            background-color: #f0f0f0;
            padding: 6px 4px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
            font-size: 10px;
        }
        table td {
            padding: 6px 4px;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .paid {
            background-color: #d4edda;
        }
        .overdue {
            background-color: #f8d7da;
        }
        .pending {
            background-color: #fff3cd;
        }
        @media print {
            body {
                margin: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'EBIMS') }}</h1>
        <h2>Loan Repayment Schedule</h2>
        <p>Printed on: {{ now()->format('d-m-Y H:i') }}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Loan Code:</span>
            <span>{{ $loan->code }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Borrower:</span>
            <span>{{ $loan->member->fname }} {{ $loan->member->lname }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Product:</span>
            <span>{{ $loan->product->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Principal Amount:</span>
            <span>UGX {{ number_format($loan->principal, 0) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Interest Rate:</span>
            <span>{{ $loan->interest }}%</span>
        </div>
        <div class="info-row">
            <span class="info-label">Loan Period:</span>
            <span>{{ $loan->period }} {{ $loan->period == 1 ? 'Week' : ($loan->period == 2 ? 'Month' : 'Days') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Due Date</th>
                <th class="text-right">Principal</th>
                <th class="text-right">Interest</th>
                <th class="text-right">Late Fees</th>
                <th class="text-right">Total Payment</th>
                <th class="text-right">Principal Paid</th>
                <th class="text-right">Interest Paid</th>
                <th class="text-right">Late Fees Paid</th>
                <th class="text-right">Total Paid</th>
                <th class="text-right">Balance</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPrincipal = 0;
                $totalInterest = 0;
                $totalLateFees = 0;
                $totalPayment = 0;
                $totalPaid = 0;
            @endphp
            @foreach($schedules as $schedule)
                @php
                    $rowClass = '';
                    $statusLabel = 'Pending';
                    if ($schedule->status == 1) {
                        $rowClass = 'paid';
                        $statusLabel = 'Paid';
                    } elseif (strtotime($schedule->installment_date) < time()) {
                        $rowClass = 'overdue';
                        $statusLabel = 'Overdue';
                    } else {
                        $rowClass = 'pending';
                        $statusLabel = 'Pending';
                    }
                    
                    $totalPrincipal += $schedule->principal;
                    $totalInterest += $schedule->interest;
                    $totalLateFees += $schedule->latepayment;
                    $totalPayment += ($schedule->principal + $schedule->interest + $schedule->latepayment);
                    $totalPaid += $schedule->amountpaid;
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="text-center">{{ $schedule->installment }}</td>
                    <td>{{ date('d-m-Y', strtotime($schedule->installment_date)) }}</td>
                    <td class="text-right">{{ number_format($schedule->principal, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->interest, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->latepayment, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->principal + $schedule->interest + $schedule->latepayment, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->principal_paid, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->interest_paid, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->latepayment_paid, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->amountpaid, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->outstanding_balance, 0) }}</td>
                    <td class="text-center">{{ $statusLabel }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #e9ecef;">
                <td colspan="2" class="text-right">TOTAL:</td>
                <td class="text-right">{{ number_format($totalPrincipal, 0) }}</td>
                <td class="text-right">{{ number_format($totalInterest, 0) }}</td>
                <td class="text-right">{{ number_format($totalLateFees, 0) }}</td>
                <td class="text-right">{{ number_format($totalPayment, 0) }}</td>
                <td colspan="4"></td>
                <td class="text-right">{{ number_format($loan->outstanding_balance, 0) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer;">Print</button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; margin-left: 10px;">Close</button>
    </div>
</body>
</html>
