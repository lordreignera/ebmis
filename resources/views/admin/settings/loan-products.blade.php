@extends('layouts.admin')

@section('title', 'Loan Products Settings')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">Loan Products</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item active">Loan Products</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Page Header Actions -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="font-weight-bold">Manage Loan Products</h3>
                        <p class="text-muted mb-0">Configure loan products, interest rates, and terms</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.loan-products.create') }}" class="btn btn-primary">
                            <i class="mdi mdi-plus"></i> Add New Product
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Active Loan Products ({{ $products->count() }})</h4>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Product Name</th>
                                        <th>Type</th>
                                        <th>Interest Rate</th>
                                        <th>Max Amount</th>
                                        <th>Period Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                    <tr>
                                        <td class="font-weight-bold">{{ $product->code }}</td>
                                        <td>
                                            <h6 class="mb-0">{{ $product->name }}</h6>
                                            @if($product->description)
                                            <small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($product->loan_type == 1)
                                                <span class="badge badge-info">Individual</span>
                                            @elseif($product->loan_type == 2)
                                                <span class="badge badge-success">Group</span>
                                            @elseif($product->loan_type == 3)
                                                <span class="badge badge-warning">Business</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $product->loan_type }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ $product->interest }}%</span>
                                        </td>
                                        <td>UGX {{ number_format($product->max_amt) }}</td>
                                        <td>
                                            @if($product->period_type == 1)
                                                Days
                                            @elseif($product->period_type == 2)
                                                Weeks
                                            @elseif($product->period_type == 3)
                                                Months
                                            @elseif($product->period_type == 4)
                                                Years
                                            @else
                                                {{ $product->period_type }}
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
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.loan-products.show', $product->id) }}" 
                                                   class="btn btn-sm btn-outline-info" title="View">
                                                    <i class="mdi mdi-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.loan-products.edit', $product->id) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                @if($product->isactive == 1)
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger" 
                                                        onclick="toggleProductStatus({{ $product->id }}, 0)"
                                                        title="Deactivate">
                                                    <i class="mdi mdi-close-circle"></i>
                                                </button>
                                                @else
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-success" 
                                                        onclick="toggleProductStatus({{ $product->id }}, 1)"
                                                        title="Activate">
                                                    <i class="mdi mdi-check-circle"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="mdi mdi-package-variant mdi-48px"></i>
                                                <h5 class="mt-2">No loan products found</h5>
                                                <p>Click "Add New Product" to create your first loan product</p>
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
            <div class="col-md-3">
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Total Products</h6>
                                <h3 class="mb-0">{{ $products->count() }}</h3>
                            </div>
                            <i class="mdi mdi-package-variant mdi-36px"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-gradient-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Active Products</h6>
                                <h3 class="mb-0">{{ $products->where('isactive', 1)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-check-circle mdi-36px"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-gradient-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Individual Loans</h6>
                                <h3 class="mb-0">{{ $products->where('loan_type', 1)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-account mdi-36px"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-gradient-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Group Loans</h6>
                                <h3 class="mb-0">{{ $products->where('loan_type', 2)->count() }}</h3>
                            </div>
                            <i class="mdi mdi-account-group mdi-36px"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
function toggleProductStatus(productId, newStatus) {
    const action = newStatus === 1 ? 'activate' : 'deactivate';
    const title = newStatus === 1 ? 'Activate Product?' : 'Deactivate Product?';
    const text = newStatus === 1 
        ? 'This product will be available for new loans' 
        : 'This product will no longer be available for new loans';
    
    Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus === 1 ? '#28a745' : '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: `Yes, ${action} it`
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/products/${productId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error!', 'Failed to update product status', 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Failed to update product status', 'error');
            });
        }
    });
}
</script>
@endpush

@endsection
