<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Trial Balance</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; }
        .header p { margin: 3px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>TRIAL BALANCE</h2>
        <p>As of: {{ \Carbon\Carbon::parse($asOfDate)->format('F d, Y') }}</p>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%">Account Code</th>
                <th style="width: 40%">Account Name</th>
                <th style="width: 15%">Category</th>
                <th style="width: 15%" class="text-right">Debit</th>
                <th style="width: 15%" class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
            <tr>
                <td>{{ $row['code'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['category'] }}</td>
                <td class="text-right">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}</td>
                <td class="text-right">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" class="text-right"><strong>TOTALS:</strong></td>
                <td class="text-right"><strong>{{ number_format($totalDebits, 2) }}</strong></td>
                <td class="text-right"><strong>{{ number_format($totalCredits, 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="3" class="text-right"><strong>DIFFERENCE:</strong></td>
                <td colspan="2" class="text-center">
                    <strong>{{ number_format(abs($totalDebits - $totalCredits), 2) }}</strong>
                    @if(abs($totalDebits - $totalCredits) < 0.01)
                        <span style="color: green;">✓ Balanced</span>
                    @else
                        <span style="color: red;">⚠ Out of Balance</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>EBMIS - Trial Balance Report | Printed by: {{ auth()->user()->name }}</p>
    </div>
</body>
</html>
