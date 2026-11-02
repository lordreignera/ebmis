@if(data_get($preview, 'fund_allocations.enabled'))
    @php($funds = $preview['fund_allocations'])
    <div class="card mt-4"><div class="card-header"><h5 class="mb-0">Officer-tagged fund allocations</h5></div><div class="card-body">
        <p class="small text-muted">The 14% bonus budget is an allocation ceiling. Four bonuses earn at most 12%. Current revenue funds next week's operating allocation; this week's report reconciles funds already received.</p>
        <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Allocation</th><th class="text-end">UGX</th></tr></thead><tbody>
            @foreach(['management_allocation' => 'Current revenue: next-week management allocation', 'revolving_fund' => 'Revolving Fund contribution', 'growth_baseline' => 'Growth Revolving Fund baseline', 'base_pay_difference' => 'Reduced base-pay difference to Growth', 'unearned_bonus_to_growth' => 'Unearned bonus budget to Growth', 'growth_total' => 'Total Growth Revolving Fund contribution', 'unallocated_bonus_held' => 'Unearned bonus held unallocated', 'operations_reserve' => 'Unused received funds to Internal Operations Reserve'] as $key => $label)
                <tr><td>{{ $label }}</td><td class="text-end">{{ !isset($funds[$key]) ? 'Awaiting verified inputs' : number_format($funds[$key], 2) }}</td></tr>
            @endforeach
            <tr><th>Current revenue allocation difference</th><th class="text-end">{{ isset($funds['reconciliation_difference']) ? number_format($funds['reconciliation_difference'], 2) : 'Unresolved' }}</th></tr>
        </tbody></table></div>
        <p class="small text-muted mb-0">Reserve transfers use verified unspent money received. Unfunded authorization remains in the management budget. Earned amounts deducted for overruns or prior deficits are never transferred again as unearned bonus.</p>
    </div></div>
@endif
