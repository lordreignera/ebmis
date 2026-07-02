@foreach($investments as $investment)
    <option value="{{ $investment->id }}" @selected((string) ($selectedInvestmentId ?? '') === (string) $investment->id)>
        {{ $investment->name }} - UGX {{ number_format((float) $investment->amount, 0) }} ({{ $investment->status_name }})
    </option>
@endforeach
