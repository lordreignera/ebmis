<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Overdue Notice - {{ $loan->code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #c82333;
        }
        .header h2 {
            margin: 10px 0;
            font-size: 20px;
            color: #666;
        }
        .notice-box {
            border: 3px solid #c82333;
            padding: 20px;
            margin: 30px 0;
            background-color: #f8d7da;
        }
        .notice-box h3 {
            margin-top: 0;
            color: #721c24;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            width: 180px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th {
            background-color: #c82333;
            color: white;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }
        table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .overdue-row {
            background-color: #f8d7da;
        }
        .total-row {
            font-weight: bold;
            background-color: #e9ecef;
            font-size: 14px;
        }
        .footer-notice {
            margin-top: 40px;
            padding: 20px;
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .signature-section {
            margin-top: 60px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            margin-top: 50px;
        }
        @media print {
            body {
                margin: 20px;
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
        <h2 style="color: #c82333;">OVERDUE PAYMENT NOTICE</h2>
        <p>Date: {{ now()->format('d-m-Y') }}</p>
    </div>

    <div class="notice-box">
        <h3>⚠ URGENT: PAYMENT OVERDUE</h3>
        <p>This is to notify you that your loan payment(s) are overdue. Please make arrangements to settle the outstanding amount immediately to avoid further penalties and potential legal action.</p>
    </div>

    <div class="info-section">
        <h3>Borrower Information</h3>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span>{{ $loan->member->fname }} {{ $loan->member->lname }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Member ID:</span>
            <span>{{ $loan->member->member_no ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span>{{ $loan->member->phone ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="info-section">
        <h3>Loan Information</h3>
        <div class="info-row">
            <span class="info-label">Loan Code:</span>
            <span>{{ $loan->code }}</span>
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

    <h3>Overdue Installments</h3>
    <table>
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Due Date</th>
                <th>Days Overdue</th>
                <th class="text-right">Principal</th>
                <th class="text-right">Interest</th>
                <th class="text-right">Late Fees</th>
                <th class="text-right">Total Due</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPrincipal = 0;
                $totalInterest = 0;
                $totalLateFees = 0;
                $totalDue = 0;
            @endphp
            @foreach($schedules as $schedule)
                @php
                    $daysOverdue = \Carbon\Carbon::parse($schedule->installment_date)->diffInDays(now());
                    $installmentDue = $schedule->principal + $schedule->interest + $schedule->latepayment;
                    
                    $totalPrincipal += $schedule->principal;
                    $totalInterest += $schedule->interest;
                    $totalLateFees += $schedule->latepayment;
                    $totalDue += $installmentDue;
                @endphp
                <tr class="overdue-row">
                    <td class="text-center">{{ $schedule->installment }}</td>
                    <td>{{ date('d-m-Y', strtotime($schedule->installment_date)) }}</td>
                    <td><strong style="color: #c82333;">{{ $daysOverdue }} days</strong></td>
                    <td class="text-right">{{ number_format($schedule->principal, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->interest, 0) }}</td>
                    <td class="text-right">{{ number_format($schedule->latepayment, 0) }}</td>
                    <td class="text-right"><strong>{{ number_format($installmentDue, 0) }}</strong></td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right">TOTAL OVERDUE AMOUNT:</td>
                <td class="text-right">{{ number_format($totalPrincipal, 0) }}</td>
                <td class="text-right">{{ number_format($totalInterest, 0) }}</td>
                <td class="text-right">{{ number_format($totalLateFees, 0) }}</td>
                <td class="text-right" style="color: #c82333; font-size: 16px;">UGX {{ number_format($totalDue, 0) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer-notice">
        <h4 style="margin-top: 0;">Payment Instructions</h4>
        <p><strong>Please settle the overdue amount within 7 days from the date of this notice.</strong></p>
        <p>You can make payment through:</p>
        <ul>
            <li>Branch office</li>
            <li>Mobile Money</li>
            <li>Bank Transfer</li>
        </ul>
        <p><strong>Contact:</strong> {{ config('app.phone', 'N/A') }} | <strong>Email:</strong> {{ config('app.email', 'info@ebims.com') }}</p>
    </div>

    <div class="signature-section">
        <p><strong>Issued by:</strong></p>
        <div class="signature-line"></div>
        <p>Authorized Officer<br>{{ config('app.name', 'EBIMS') }}</p>
    </div>

    <div class="no-print" style="margin-top: 30px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; background-color: #c82333; color: white; border: none;">Print Notice</button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; margin-left: 10px;">Close</button>
    </div>
</body>
</html>
