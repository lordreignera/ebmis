<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Statement - {{ $loan->code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
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
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
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
        <h2>Loan Payment Statement</h2>
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
            <span class="info-label">Loan Date:</span>
            <span>{{ date('d-m-Y', strtotime($loan->datecreated)) }}</span>
        </div>
    </div>

    <h3>Payment History</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Description</th>
                <th class="text-right">Principal</th>
                <th class="text-right">Interest</th>
                <th class="text-right">Late Fees</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPrincipal = 0;
                $totalInterest = 0;
                $totalLateFees = 0;
                $totalAmount = 0;
            @endphp
            @forelse($repayments as $index => $repayment)
                @php
                    $totalPrincipal += $repayment->principal_paid;
                    $totalInterest += $repayment->interest_paid;
                    $totalLateFees += $repayment->latepayment;
                    $totalAmount += $repayment->amount;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ date('d-m-Y', strtotime($repayment->created_at)) }}</td>
                    <td>Payment</td>
                    <td class="text-right">{{ number_format($repayment->principal_paid, 0) }}</td>
                    <td class="text-right">{{ number_format($repayment->interest_paid, 0) }}</td>
                    <td class="text-right">{{ number_format($repayment->latepayment, 0) }}</td>
                    <td class="text-right">{{ number_format($repayment->amount, 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center;">No payments recorded</td>
                </tr>
            @endforelse
            @if($repayments->count() > 0)
                <tr class="total-row">
                    <td colspan="3">TOTAL PAID</td>
                    <td class="text-right">{{ number_format($totalPrincipal, 0) }}</td>
                    <td class="text-right">{{ number_format($totalInterest, 0) }}</td>
                    <td class="text-right">{{ number_format($totalLateFees, 0) }}</td>
                    <td class="text-right">{{ number_format($totalAmount, 0) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div style="margin-top: 30px;">
        <div class="info-row">
            <span class="info-label">Outstanding Balance:</span>
            <span style="font-weight: bold; font-size: 16px;">UGX {{ number_format($loan->outstanding_balance, 0) }}</span>
        </div>
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer;">Print</button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; margin-left: 10px;">Close</button>
    </div>
</body>
</html>
