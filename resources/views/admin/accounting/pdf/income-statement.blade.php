<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Income Statement</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; }
        .header p { margin: 3px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .category-header { background-color: #e9ecef; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .section-total { background-color: #d6d8db; font-weight: bold; }
        .net-income { background-color: #28a745; color: white; font-weight: bold; }
        .net-loss { background-color: #dc3545; color: white; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>INCOME STATEMENT (PROFIT & LOSS)</h2>
        <p>Period: {{ \Carbon\Carbon::parse($dateFrom)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('F d, Y') }}</p>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 20%">Account Code</th>
                <th style="width: 60%">Account Name</th>
                <th style="width: 20%" class="text-right">Amount (UGX)</th>
            </tr>
        </thead>
        <tbody>
            <!-- INCOME/REVENUE -->
            <tr class="category-header">
                <td colspan="3"><strong>REVENUE</strong></td>
            </tr>
            @forelse($income as $item)
            <tr>
                <td>{{ $item['code'] }}</td>
                <td>&nbsp;&nbsp;{{ $item['name'] }}</td>
                <td class="text-right">{{ number_format($item['balance'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; color: #999;">No income recorded for this period</td>
            </tr>
            @endforelse
            <tr class="section-total">
                <td colspan="2" class="text-right"><strong>TOTAL REVENUE</strong></td>
                <td class="text-right"><strong>{{ number_format($totalIncome, 2) }}</strong></td>
            </tr>

            <!-- EXPENSES -->
            <tr class="category-header">
                <td colspan="3"><strong>EXPENSES</strong></td>
            </tr>
            @forelse($expenses as $expense)
            <tr>
                <td>{{ $expense['code'] }}</td>
                <td>&nbsp;&nbsp;{{ $expense['name'] }}</td>
                <td class="text-right">{{ number_format($expense['balance'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center; color: #999;">No expenses recorded for this period</td>
            </tr>
            @endforelse
            <tr class="section-total">
                <td colspan="2" class="text-right"><strong>TOTAL EXPENSES</strong></td>
                <td class="text-right"><strong>{{ number_format($totalExpenses, 2) }}</strong></td>
            </tr>

            <!-- NET INCOME/LOSS -->
            <tr class="{{ $netIncome >= 0 ? 'net-income' : 'net-loss' }}">
                <td colspan="2" class="text-right"><strong>{{ $netIncome >= 0 ? 'NET INCOME' : 'NET LOSS' }}</strong></td>
                <td class="text-right"><strong>{{ number_format(abs($netIncome), 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>EBMIS - Income Statement Report | Printed by: {{ auth()->user()->name }}</p>
    </div>
</body>
</html>
