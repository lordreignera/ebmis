<tr>
    <td><input class="form-control form-control-sm" name="items[{{ $index }}][date]" type="date" min="{{ $periodStart->toDateString() }}" max="{{ $periodEnd->toDateString() }}" value="{{ $item['date'] ?? '' }}"></td>
    <td><select class="form-select form-select-sm" name="items[{{ $index }}][category]">@foreach(['transport' => 'Transport', 'communication' => 'Communication', 'field_visit' => 'Field visit', 'stationery' => 'Stationery', 'other' => 'Other actual cost'] as $code => $label)<option value="{{ $code }}" @selected(($item['category'] ?? '') === $code)>{{ $label }}</option>@endforeach</select></td>
    <td><input class="form-control form-control-sm" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" maxlength="500"></td>
    <td><input class="form-control form-control-sm" name="items[{{ $index }}][amount]" type="number" min="0.01" step="0.01" value="{{ $item['amount'] ?? '' }}"></td>
    <td><input class="form-control form-control-sm" name="items[{{ $index }}][reference]" value="{{ $item['reference'] ?? '' }}" maxlength="500"></td>
    <td><input class="form-control form-control-sm" name="items[{{ $index }}][expenditure_id]" type="number" min="1" value="{{ $item['expenditure_id'] ?? '' }}" placeholder="Optional"></td>
    <td><button class="btn btn-sm btn-outline-secondary" type="button" data-remove-expense aria-label="Remove expense line">Remove</button></td>
</tr>
