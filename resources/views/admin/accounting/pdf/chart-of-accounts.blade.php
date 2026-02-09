<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Chart of Accounts</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; }
        .header p { margin: 3px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .category-header { background-color: #e9ecef; font-weight: bold; }
        .sub-account { padding-left: 25px; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
        .status-active { color: green; }
        .status-inactive { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h2>CHART OF ACCOUNTS</h2>
        <p>Complete System Accounts Listing</p>
        @if(isset($startDate) || isset($endDate))
            <p><strong>Balance as of {{ $endDate ?? date('Y-m-d') }}</strong></p>
            @if(isset($startDate) && $startDate)
                <p>Period: {{ $startDate }} to {{ $endDate ?? date('Y-m-d') }}</p>
            @endif
        @else
            <p>All Time Balances</p>
        @endif
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
    </div>

    @foreach(['Asset', 'Liability', 'Equity', 'Income', 'Expense'] as $category)
        @if(isset($accounts[$category]) && $accounts[$category]->count() > 0)
        <table>
            <thead>
                <tr class="category-header">
                    <td colspan="6"><strong>{{ strtoupper($category) }} ACCOUNTS</strong></td>
                </tr>
                <tr>
                    <th style="width: 10%">Code</th>
                    <th style="width: 10%">Sub Code</th>
                    <th style="width: 40%">Account Name</th>
                    <th style="width: 15%">Balance</th>
                    <th style="width: 15%">Parent Code</th>
                    <th style="width: 10%">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($accounts[$category] as $account)
                <tr>
                    <td>{{ $account->code }}</td>
                    <td>{{ $account->sub_code ?? '-' }}</td>
                    <td class="{{ $account->parent_account ? 'sub-account' : '' }}">
                        {{ $account->name }}
                    </td>
                    <td style="text-align: right;">
                        @if($account->running_balance != 0)
                            {{ number_format(abs($account->running_balance), 2) }}
                        @else
                            0.00
                        @endif
                    </td>
                    <td>{{ $account->parent ? $account->parent->code : '-' }}</td>
                    <td class="{{ $account->status == 1 ? 'status-active' : 'status-inactive' }}">
                        {{ $account->status == 1 ? 'Active' : 'Inactive' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <br>
        @endif
    @endforeach

    <div class="footer">
        <p>EBMIS - Chart of Accounts | Total Accounts: {{ $accounts->flatten()->count() }} | Printed by: {{ auth()->user()->name }}</p>
    </div>
</body>
</html>
