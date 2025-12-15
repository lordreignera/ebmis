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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="mdi mdi-plus"></i> Add New Product
                        </button>
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
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-info" onclick="editProduct({{ $product->id }})" data-product='@json($product)'>
                                                    <i class="mdi mdi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-{{ $product->isactive ? 'warning' : 'success' }}" onclick="toggleStatus({{ $product->id }}, {{ $product->isactive }})">
                                                    <i class="mdi mdi-{{ $product->isactive ? 'pause' : 'play' }}"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteProduct({{ $product->id }})">
                                                    <i class="mdi mdi-delete"></i>
                                                </button>
                                            </div>
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

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white; min-width: 500px;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;"><i class="mdi mdi-bank-plus"></i> Add New Savings Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.savings-products.store') }}" method="POST">
                @csrf
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="add_code" class="form-label" style="color: #000;">Product Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_code" name="code" readonly style="background-color: #e9ecef; color: #000;">
                        <small class="text-muted">Auto-generated</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="add_name" class="form-label" style="color: #000;">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required placeholder="e.g., Regular Savings" style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="add_interest" class="form-label" style="color: #000;">Interest Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="add_interest" name="interest" step="0.01" min="0" max="100" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="add_min_amt" class="form-label" style="color: #000;">Minimum Amount (UGX) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="add_min_amt" name="min_amt" min="0" required style="background-color: white; color: #000;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="add_max_amt" class="form-label" style="color: #000;">Maximum Amount (UGX) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="add_max_amt" name="max_amt" min="0" required style="background-color: white; color: #000;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="add_charge" class="form-label" style="color: #000;">Charge (UGX)</label>
                        <input type="number" class="form-control" id="add_charge" name="charge" min="0" step="0.01" style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="add_account" class="form-label" style="color: #000;">System Account <span class="text-danger">*</span></label>
                        <select class="form-control" id="add_account" name="account" required style="background-color: white; color: #000;">
                            <option value="">Select Account</option>
                            @foreach($systemAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="add_isactive" name="isactive" value="1" checked>
                            <label class="form-check-label" for="add_isactive" style="color: #000;">
                                Active
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="add_description" class="form-label" style="color: #000;">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3" placeholder="Product description" style="background-color: white; color: #000;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-primary"><i class="mdi mdi-check"></i> Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white; min-width: 500px;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;"><i class="mdi mdi-pencil"></i> Edit Savings Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editProductForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="edit_code" class="form-label" style="color: #000;">Product Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_code" name="code" readonly style="background-color: #e9ecef; color: #000;">
                        <small class="text-muted">Cannot be changed</small>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_name" class="form-label" style="color: #000;">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_interest" class="form-label" style="color: #000;">Interest Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="edit_interest" name="interest" step="0.01" min="0" max="100" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_min_amt" class="form-label" style="color: #000;">Minimum Amount (UGX) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_min_amt" name="min_amt" min="0" required style="background-color: white; color: #000;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_max_amt" class="form-label" style="color: #000;">Maximum Amount (UGX) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="edit_max_amt" name="max_amt" min="0" required style="background-color: white; color: #000;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_charge" class="form-label" style="color: #000;">Charge (UGX)</label>
                        <input type="number" class="form-control" id="edit_charge" name="charge" min="0" step="0.01" style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_account" class="form-label" style="color: #000;">System Account <span class="text-danger">*</span></label>
                        <select class="form-control" id="edit_account" name="account" required style="background-color: white; color: #000;">
                            <option value="">Select Account</option>
                            @foreach($systemAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_isactive" name="isactive" value="1">
                            <label class="form-check-label" for="edit_isactive" style="color: #000;">
                                Active
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="edit_description" class="form-label" style="color: #000;">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" style="background-color: white; color: #000;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-primary"><i class="mdi mdi-check"></i> Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Generate product code when Add modal opens
document.getElementById('addProductModal').addEventListener('show.bs.modal', function () {
    // Generate code like BSV1738908165 (BSV + timestamp)
    const code = 'BSV' + Math.floor(Date.now() / 1000);
    document.getElementById('add_code').value = code;
});

function editProduct(productId) {
    const btn = event.target.closest('button');
    const product = JSON.parse(btn.getAttribute('data-product'));
    
    document.getElementById('edit_code').value = product.code;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_interest').value = product.interest;
    document.getElementById('edit_min_amt').value = product.min_amt;
    document.getElementById('edit_max_amt').value = product.max_amt;
    document.getElementById('edit_charge').value = product.charge || '';
    document.getElementById('edit_account').value = product.account || '';
    document.getElementById('edit_description').value = product.description || '';
    document.getElementById('edit_isactive').checked = product.isactive == 1;
    
    document.getElementById('editProductForm').action = `/admin/savings-products/${product.id}`;
    
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

function toggleStatus(productId, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    const icon = currentStatus ? 'warning' : 'question';
    
    Swal.fire({
        title: `${action.charAt(0).toUpperCase() + action.slice(1)} Product?`,
        text: currentStatus ? "This product will no longer be available for new accounts" : "This product will be available for new accounts",
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: currentStatus ? '#d33' : '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: `Yes, ${action} it`
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/admin/savings-products/${productId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Success!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'Failed to update product status', 'error');
            });
        }
    });
}

function deleteProduct(productId) {
    if (typeof Swal === 'undefined') {
        if (confirm('Delete Product? This action cannot be undone!')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/savings-products/${productId}`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
        return;
    }
    
    Swal.fire({
        title: 'Delete Product?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/admin/savings-products/${productId}`;
            
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
            
            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            
            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush

@endsection
