<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Journal Entries</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 5px 0; }
        .header p { margin: 3px 0; color: #666; }
        .entry { margin-bottom: 25px; page-break-inside: avoid; }
        .entry-header { background-color: #f4f4f4; padding: 8px; border: 1px solid #ddd; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f9f9f9; font-weight: bold; font-size: 10px; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
        .footer { margin-top: 20px; font-size: 10px; color: #666; text-align: center; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h2>JOURNAL ENTRIES LISTING</h2>
        <p>Period: {{ $dateFrom != 'All' ? \Carbon\Carbon::parse($dateFrom)->format('M d, Y') : 'All' }} 
           to {{ $dateTo != 'All' ? \Carbon\Carbon::parse($dateTo)->format('M d, Y') : 'All' }}</p>
        <p>Generated: {{ now()->format('F d, Y h:i A') }}</p>
        <p>Total Entries: {{ $entries->count() }}</p>
    </div>

    @foreach($entries as $index => $entry)
        <div class="entry {{ ($index + 1) % 3 == 0 ? 'page-break' : '' }}">
            <div class="entry-header">
                Journal #: {{ $entry->journal_number }} | 
                Date: {{ \Carbon\Carbon::parse($entry->transaction_date)->format('M d, Y') }} | 
                Type: {{ ucfirst(str_replace('_', ' ', $entry->reference_type ?? 'General')) }} |
                Status: {{ ucfirst($entry->status) }}
            </div>
            <p style="margin: 5px 0; padding-left: 8px;"><strong>Description:</strong> {{ $entry->description }}</p>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 15%">Account Code</th>
                        <th style="width: 50%">Account Name</th>
                        <th style="width: 17.5%" class="text-right">Debit</th>
                        <th style="width: 17.5%" class="text-right">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalDebit = 0;
                        $totalCredit = 0;
                    @endphp
                    @foreach($entry->lines as $line)
                    <tr>
                        <td>{{ $line->account->code ?? 'N/A' }}</td>
                        <td>{{ $line->account->name ?? 'Unknown' }}</td>
                        <td class="text-right">{{ $line->debit_amount > 0 ? number_format($line->debit_amount, 2) : '-' }}</td>
                        <td class="text-right">{{ $line->credit_amount > 0 ? number_format($line->credit_amount, 2) : '-' }}</td>
                    </tr>
                    @php
                        $totalDebit += $line->debit_amount;
                        $totalCredit += $line->credit_amount;
                    @endphp
                    @endforeach
                    <tr class="total-row">
                        <td colspan="2" class="text-right"><strong>TOTALS:</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalDebit, 2) }}</strong></td>
                        <td class="text-right"><strong>{{ number_format($totalCredit, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="footer">
        <p>EBMIS - Journal Entries Report | Printed by: {{ auth()->user()->name }} | Page {{ $entries->count() > 20 ? 'Multiple' : '1' }}</p>
    </div>
</body>
</html>
