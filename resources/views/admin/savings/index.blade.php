@extends('layouts.admin')

@section('title', $pageTitle)

@section('content')
@php
    $actions = '<a href="' . route('admin.savings.create') . '" class="btn btn-success">'
        . '<i class="mdi mdi-plus me-1"></i> New Savings Deposit</a>';
@endphp

<div class="container-fluid">
    @include('admin.partials.page-header', [
        'title' => $pageTitle,
        'subtitle' => 'Review client savings deposits, confirmations, branches, and products.',
        'icon' => 'mdi mdi-piggy-bank',
        'actions' => $actions,
    ])

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Deposit reference or client">
                </div>
                @unless(request()->routeIs('admin.savings.pending', 'admin.savings.approved'))
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All statuses</option>
                            <option value="0" @selected(request('status') === '0')>Pending</option>
                            <option value="1" @selected(request('status') === '1')>Confirmed</option>
                            <option value="2" @selected(request('status') === '2')>Rejected</option>
                            <option value="3" @selected(request('status') === '3')>Reversed</option>
                        </select>
                    </div>
                @endunless
                <div class="col-md-2">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">All branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-select">
                        <option value="">All products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-dark">Filter</button>
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Deposit</th>
                            <th>Client</th>
                            <th>Product</th>
                            <th>Branch</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($savings as $saving)
                            @php
                                $badge = match((int) $saving->status) {
                                    1 => 'success',
                                    2, 3 => 'danger',
                                    default => 'warning',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <strong>SAV-{{ str_pad((string) $saving->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                    @if($saving->txn_id)<small class="d-block text-muted">{{ $saving->txn_id }}</small>@endif
                                </td>
                                <td>
                                    {{ trim(($saving->member->fname ?? '') . ' ' . ($saving->member->lname ?? '')) ?: ($saving->sperson ?: 'Unknown client') }}
                                    <small class="d-block text-muted">{{ $saving->member->code ?? '' }}</small>
                                </td>
                                <td>{{ $saving->product->name ?? 'Legacy product' }}</td>
                                <td>{{ $saving->branch->name ?? 'N/A' }}</td>
                                <td>{{ optional($saving->datecreated)->format('Y-m-d H:i') ?? optional($saving->sdate)->format('Y-m-d') }}</td>
                                <td class="text-end fw-semibold">UGX {{ number_format((float) $saving->value, 0) }}</td>
                                <td><span class="badge bg-{{ $badge }}">{{ $saving->status_name }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No savings deposits match the selected filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">{{ $savings->links() }}</div>
    </div>
</div>
@endsection
