@extends('layouts.admin')

@section('title', 'Remuneration Policies')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Weekly remuneration policies</h4>
            <p class="text-muted mb-0">Versioned settings for the 12% / 5% base and four independent bonuses.</p>
        </div>
        <div>
            <a href="{{ route('admin.expenditures.remuneration') }}" class="btn btn-outline-secondary me-2">Review officers</a>
            <a href="{{ route('admin.expenditures.remuneration.policies.create') }}" class="btn btn-dark"><i class="mdi mdi-plus me-1"></i> New policy version</a>
        </div>
    </div>

    @include('admin.expenditures.partials.alerts')

    <div class="alert alert-info border-0">
        Editing is limited to draft versions. Approved and active versions are preserved for historical audit. Retiring a policy changes its status only; it does not delete records.
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Version</th><th>Status</th><th>Base rule</th><th>Bonuses</th><th>Effective</th><th>Created</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse($policies as $policy)
                    <tr>
                        <td><strong>{{ $policy->version }}</strong><small class="d-block text-muted">{{ $policy->title }}</small></td>
                        <td><span class="badge bg-{{ $policy->status === 'active' ? 'success' : ($policy->status === 'draft' ? 'warning text-dark' : ($policy->status === 'retired' ? 'secondary' : 'primary')) }}">{{ ucfirst($policy->status) }}</span></td>
                        <td>{{ number_format((float) $policy->base_rate_above_threshold, 3) }}% at/above {{ number_format((float) $policy->base_current_loan_threshold, 3) }}%<small class="d-block text-muted">{{ number_format((float) $policy->base_rate_at_or_below_threshold, 3) }}% below</small></td>
                        <td>4 × up to 3%</td>
                        <td>{{ optional($policy->effective_from)->format('Y-m-d') ?: 'Not set' }}<small class="d-block text-muted">{{ optional($policy->effective_to)->format('Y-m-d') ?: 'Open' }}</small></td>
                        <td>{{ optional($policy->createdBy)->name ?: 'System' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.expenditures.remuneration.policies.show', $policy) }}" class="btn btn-sm btn-outline-dark">View</a>
                            @if($policy->status === 'draft')
                                <a href="{{ route('admin.expenditures.remuneration.policies.edit', $policy) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                <form method="POST" action="{{ route('admin.expenditures.remuneration.policies.approve', $policy) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form>
                            @elseif($policy->status === 'approved')
                                <form method="POST" action="{{ route('admin.expenditures.remuneration.policies.activate', $policy) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Activate</button></form>
                            @endif
                            @if($policy->status !== 'retired')
                                <a href="{{ route('admin.expenditures.remuneration.policies.create', ['from' => $policy->id]) }}" class="btn btn-sm btn-outline-secondary">{{ $policy->status === 'draft' ? 'Copy version' : 'Edit as new version' }}</a>
                            @endif
                            @if($policy->status !== 'retired' && $policy->status !== 'active')
                                <form method="POST" action="{{ route('admin.expenditures.remuneration.policies.destroy', $policy) }}" class="d-inline" onsubmit="return confirm('Retire this policy version? No records will be deleted.');">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Retire</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted">No remuneration policies configured.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">{{ $policies->links() }}</div>
    </div>
</div>
@endsection
