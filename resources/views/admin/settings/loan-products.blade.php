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
                                                <span class="badge badge-info">Personal Loan</span>
                                            @elseif($product->loan_type == 2)
                                                <span class="badge badge-success">Group Loan</span>
                                            @elseif($product->loan_type == 3)
                                                <span class="badge badge-warning">Business Loan</span>
                                            @elseif($product->loan_type == 4)
                                                <span class="badge badge-primary">School Loan</span>
                                            @elseif($product->loan_type == 5)
                                                <span class="badge badge-secondary">Student Loan</span>
                                            @elseif($product->loan_type == 6)
                                                <span class="badge badge-dark">Staff Loan</span>
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
                                                Daily
                                            @elseif($product->period_type == 2)
                                                Weekly
                                            @elseif($product->period_type == 3)
                                                Monthly
                                            @elseif($product->period_type == 4)
                                                Yearly
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
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-info btn-view-product" 
                                                        data-id="{{ $product->id }}"
                                                        title="View">
                                                    <i class="mdi mdi-eye"></i>
                                                </button>
                                                <a href="{{ route('admin.loan-products.edit', $product->id) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger btn-delete-product" 
                                                        data-id="{{ $product->id }}"
                                                        data-name="{{ $product->name }}"
                                                        title="Delete">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
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

<!-- View Product Modal -->
<div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0dcaf0; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="viewProductModalLabel">View Product Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewProductContent" style="background-color: white;">
                <div class="text-center py-4">
                    <i class="mdi mdi-loading mdi-spin"></i> Loading...
                </div>
            </div>
            <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

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
                    'X-CSRF-TOKEN': csrfToken
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

// View Product Details
document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.btn-view-product');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            
            const viewContent = document.getElementById('viewProductContent');
            viewContent.innerHTML = '<div class="text-center py-4"><i class="mdi mdi-loading mdi-spin"></i> Loading...</div>';
            
            new bootstrap.Modal(document.getElementById('viewProductModal')).show();
            
            fetch(`/admin/products/${productId}`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.product) {
                    const product = data.product;
                    const loanTypes = {1: 'Personal Loan', 2: 'Group Loan', 3: 'Business Loan', 4: 'School Loan', 5: 'Student Loan', 6: 'Staff Loan'};
                    const periodTypes = {1: 'Daily', 2: 'Weekly', 3: 'Monthly', 4: 'Yearly'};
                    
                    const html = `
                        <div class="mb-3" style="background-color: #ffffff; color: #333333;">
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Product Code:</strong> ${product.code || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Product Name:</strong> ${product.name || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Description:</strong> ${product.description || 'N/A'}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Loan Type:</strong> <span class="badge badge-info">${loanTypes[product.loan_type] || product.loan_type}</span></p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Interest Rate:</strong> <span class="badge badge-primary">${product.interest}%</span></p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Maximum Amount:</strong> UGX ${parseFloat(product.max_amt).toLocaleString()}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Period Type:</strong> ${periodTypes[product.period_type] || product.period_type}</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Cash Security:</strong> ${product.cash_sceurity}%</p>
                            <p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Status:</strong> <span class="badge ${product.isactive == 1 ? 'badge-success' : 'badge-danger'}">${product.isactive == 1 ? 'Active' : 'Inactive'}</span></p>
                            ${data.account ? '<p class="mb-2" style="color: #333333;"><strong style="color: #000000;">Account:</strong> ' + data.account.account_name + '</p>' : ''}
                        </div>
                    `;
                    viewContent.innerHTML = html;
                } else {
                    viewContent.innerHTML = '<div class="alert alert-danger">Failed to load product details</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                viewContent.innerHTML = '<div class="alert alert-danger">Failed to load product details: ' + error.message + '</div>';
            });
        });
    });

    // ============= DELETE PRODUCT =============
    const deleteButtons = document.querySelectorAll('.btn-delete-product');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            
            Swal.fire({
                title: 'Are you sure?',
                text: `Delete product "${productName}"? This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/admin/products/${productId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message || 'Product deleted successfully',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'Failed to delete product'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the product'
                        });
                    });
                }
            });
        });
    });
});
</script>
@endpush

@endsection
