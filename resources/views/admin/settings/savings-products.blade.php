@extends('layouts.admin')

@section('title', 'Savings Products Settings')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">Savings Products</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item active">Savings Products</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Page Header Actions -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="font-weight-bold">Manage Savings Products</h3>
                        <p class="text-muted mb-0">Configure savings products, interest rates, and account terms</p>
                    </div>
                    <div>
                        <!-- Product creation is managed through legacy system -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Active Savings Products ({{ $savingsProducts->count() }})</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Product Name</th>
                                        <th>Interest Rate</th>
                                        <th>Min Amount</th>
                                        <th>Max Amount</th>
                                        <th>Charge</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($savingsProducts as $product)
                                    <tr>
                                        <td class="font-weight-bold">{{ $product->code }}</td>
                                        <td>
                                            <h6 class="mb-0">{{ $product->name }}</h6>
                                            @if($product->description)
                                            <small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-success">{{ $product->interest }}%</span>
                                        </td>
                                        <td>UGX {{ number_format($product->min_amt) }}</td>
                                        <td>UGX {{ number_format($product->max_amt) }}</td>
                                        <td>
                                            @if($product->charge)
                                                UGX {{ number_format($product->charge) }}
                                            @else
                                                <span class="text-muted">No charge</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->isactive == 1)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small">View only</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="mdi mdi-bank mdi-48px"></i>
                                                <h5 class="mt-2">No savings products found</h5>
                                                <p>Click "Add New Product" to create your first savings product</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Statistics -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-gradient-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Products</h6>
                                <h3 class="mb-0">{{ $savingsProducts->count() }}</h3>
                            </div>
                            <i class="mdi mdi-bank mdi-36px"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-gradient-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Active Products</h6>
                                <h3 class="mb-0">{{ $savingsProducts->where('isactive', 1)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-check-circle mdi-36px"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-gradient-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Avg Interest Rate</h6>
                                <h3 class="mb-0">{{ number_format($savingsProducts->avg('interest'), 2) }}%</h3>
                            </div>
                            <i class="mdi mdi-percent mdi-36px"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function deactivateProduct(productId) {
    Swal.fire({
        title: 'Deactivate Product?',
        text: "This product will no longer be available for new accounts",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, deactivate it'
    }).then((result) => {
        if (result.isConfirmed) {
            // TODO: Implement deactivation via AJAX
            Swal.fire('Deactivated!', 'The product has been deactivated.', 'success');
        }
    });
}

function activateProduct(productId) {
    Swal.fire({
        title: 'Activate Product?',
        text: "This product will be available for new accounts",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, activate it'
    }).then((result) => {
        if (result.isConfirmed) {
            // TODO: Implement activation via AJAX
            Swal.fire('Activated!', 'The product has been activated.', 'success');
        }
    });
}
</script>
@endpush

@endsection
