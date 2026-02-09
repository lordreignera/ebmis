<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
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
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>BALANCE SHEET</h2>
        <p>As of: {{ \Carbon\Carbon::parse($asOfDate)->format('F d, Y') }}</p>
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
            <!-- ASSETS -->
            <tr class="category-header">
                <td colspan="3"><strong>ASSETS</strong></td>
            </tr>
            @foreach($assets as $asset)
            <tr>
                <td>{{ $asset['code'] }}</td>
                <td>&nbsp;&nbsp;{{ $asset['name'] }}</td>
                <td class="text-right">{{ number_format($asset['balance'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="section-total">
                <td colspan="2" class="text-right"><strong>TOTAL ASSETS</strong></td>
                <td class="text-right"><strong>{{ number_format($totalAssets, 2) }}</strong></td>
            </tr>

            <!-- LIABILITIES -->
            <tr class="category-header">
                <td colspan="3"><strong>LIABILITIES</strong></td>
            </tr>
            @foreach($liabilities as $liability)
            <tr>
                <td>{{ $liability['code'] }}</td>
                <td>&nbsp;&nbsp;{{ $liability['name'] }}</td>
                <td class="text-right">{{ number_format($liability['balance'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="section-total">
                <td colspan="2" class="text-right"><strong>TOTAL LIABILITIES</strong></td>
                <td class="text-right"><strong>{{ number_format($totalLiabilities, 2) }}</strong></td>
            </tr>

            <!-- EQUITY -->
            <tr class="category-header">
                <td colspan="3"><strong>EQUITY</strong></td>
            </tr>
            @foreach($equity as $eq)
            <tr>
                <td>{{ $eq['code'] }}</td>
                <td>&nbsp;&nbsp;{{ $eq['name'] }}</td>
                <td class="text-right">{{ number_format($eq['balance'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="section-total">
                <td colspan="2" class="text-right"><strong>TOTAL EQUITY</strong></td>
                <td class="text-right"><strong>{{ number_format($totalEquity, 2) }}</strong></td>
            </tr>

            <!-- TOTAL LIABILITIES + EQUITY -->
            <tr class="total-row">
                <td colspan="2" class="text-right"><strong>TOTAL LIABILITIES + EQUITY</strong></td>
                <td class="text-right"><strong>{{ number_format($totalLiabilities + $totalEquity, 2) }}</strong></td>
            </tr>

            <!-- BALANCE CHECK -->
            <tr>
                <td colspan="3" class="text-center">
                    @if(abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01)
                        <span style="color: green;"><strong>✓ Accounting Equation Balanced</strong></span>
                    @else
                        <span style="color: red;"><strong>⚠ Out of Balance by {{ number_format(abs($totalAssets - ($totalLiabilities + $totalEquity)), 2) }}</strong></span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>EBMIS - Balance Sheet Report | Printed by: {{ auth()->user()->name }}</p>
    </div>
</body>
</html>
