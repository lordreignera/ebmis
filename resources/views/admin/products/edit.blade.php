@extends('layouts.admin')

@section('title', 'Edit Loan Product')

@push('styles')
<style>
    .nav-tabs .nav-link {
        color: #6c757d;
        border: none;
        border-bottom: 2px solid transparent;
        padding: 1rem 1.5rem;
    }
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom: 2px solid #0d6efd;
        background: transparent;
    }
    .tab-content {
        padding: 2rem 0;
    }
    .charge-row {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">Edit Loan Product</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.loan-products') }}">Loan Products</a></li>
                        <li class="breadcrumb-item active">Edit Product</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs" id="productTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                                    <i class="mdi mdi-account-details"></i> Product Details
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="parameters-tab" data-bs-toggle="tab" data-bs-target="#parameters" type="button" role="tab">
                                    <i class="mdi mdi-cog"></i> Product Parameters
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="charges-tab" data-bs-toggle="tab" data-bs-target="#charges" type="button" role="tab">
                                    <i class="mdi mdi-bell-ring"></i> Product Upfront Charges
                                </button>
                            </li>
                        </ul>

                        <!-- Tabs Content -->
                        <div class="tab-content" id="productTabContent">
                            <!-- Product Details Tab -->
                            <div class="tab-pane fade show active" id="details" role="tabpanel">
                                <form id="productDetailsForm">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="name">Product Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="name" name="name" value="{{ $product->name }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="fees_account">Fees Account <span class="text-danger">*</span></label>
                                                <select class="form-control" id="fees_account" name="account" required>
                                                    <option value="">Select</option>
                                                    @foreach(\App\Models\SystemAccount::where('status', 1)->get() as $acc)
                                                        @php
                                                            $accId = $acc->Id ?? $acc->id;
                                                        @endphp
                                                        <option value="{{ $accId }}" {{ (string)$product->account === (string)$accId ? 'selected' : '' }}>
                                                            {{ $acc->code }} - {{ $acc->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="loan_type">Loan Type <span class="text-danger">*</span></label>
                                                <select class="form-control" id="loan_type" name="loan_type" required>
                                                    <option value="">Select Loan Type</option>
                                                    <option value="1" {{ $product->loan_type == 1 ? 'selected' : '' }}>Personal Loan</option>
                                                    <option value="2" {{ $product->loan_type == 2 ? 'selected' : '' }}>Group Loan</option>
                                                    <option value="3" {{ $product->loan_type == 3 ? 'selected' : '' }}>Business Loan</option>
                                                    <option value="4" {{ $product->loan_type == 4 ? 'selected' : '' }}>School Loan</option>
                                                    <option value="5" {{ $product->loan_type == 5 ? 'selected' : '' }}>Student Loan</option>
                                                    <option value="6" {{ $product->loan_type == 6 ? 'selected' : '' }}>Staff Loan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="type">Product Type <span class="text-danger">*</span></label>
                                                <select class="form-control" id="type" name="type" required>
                                                    <option value="">Select Product Type</option>
                                                    <option value="1" {{ $product->type == 1 ? 'selected' : '' }}>Fixed Term Loan</option>
                                                    <option value="2" {{ $product->type == 2 ? 'selected' : '' }}>Revolving Credit</option>
                                                    <option value="3" {{ $product->type == 3 ? 'selected' : '' }}>Emergency Loan</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="period_type">Period Type <span class="text-danger">*</span></label>
                                                <select class="form-control" id="period_type" name="period_type" required>
                                                    <option value="">Select Period Type</option>
                                                    <option value="3" {{ $product->period_type == 3 ? 'selected' : '' }}>Daily</option>
                                                    <option value="1" {{ $product->period_type == 1 ? 'selected' : '' }}>Weekly</option>
                                                    <option value="2" {{ $product->period_type == 2 ? 'selected' : '' }}>Monthly</option>
                                                    <option value="4" {{ $product->period_type == 4 ? 'selected' : '' }}>Yearly</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="description">Product Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4">{{ $product->description }}</textarea>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="mdi mdi-content-save"></i> Update Product Details
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Product Parameters Tab -->
                            <div class="tab-pane fade" id="parameters" role="tabpanel">
                                <form id="productParametersForm">
                                    @csrf
                                    @method('PUT')
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="max_amt">Max Loan Amount <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control amount-input" id="max_amt_display" value="{{ number_format($product->max_amt, 0) }}" required>
                                                <input type="hidden" id="max_amt" name="max_amt" value="{{ $product->max_amt }}">
                                                <small class="text-muted">Maximum amount that can be borrowed</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="interest">Interest (%age per repayment period) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control" id="interest" name="interest" value="{{ $product->interest }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="cash_sceurity">Cash Security (%age required 0-100) <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control" id="cash_sceurity" name="cash_sceurity" value="{{ $product->cash_sceurity }}" min="0" max="100" required>
                                                <small class="text-muted">Percentage of loan amount required as security deposit</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="mdi mdi-content-save"></i> Update Product Parameters
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Product Charges Tab -->
                            <div class="tab-pane fade" id="charges" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">Product Charges <small class="text-muted">Charges Levied at Creation of Loan Account</small></h5>
                                    <button type="button" class="btn btn-primary btn-sm" id="addChargeBtn">
                                        <i class="mdi mdi-plus"></i> Add Product Charge
                                    </button>
                                </div>

                                <div id="chargesContainer">
                                    @forelse($product->charges as $index => $charge)
                                    <div class="charge-row" data-charge-id="{{ $charge->id }}">
                                        <div class="row align-items-end">
                                            <div class="col-md-1">
                                                <strong>#{{ $index + 1 }}</strong>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Charge Name</label>
                                                <input type="text" class="form-control" value="{{ $charge->name }}" data-field="name">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Charge Type</label>
                                                <select class="form-control" data-field="type">
                                                    <option value="1" {{ $charge->type == 1 ? 'selected' : '' }}>Fixed Amount</option>
                                                    <option value="2" {{ $charge->type == 2 ? 'selected' : '' }}>Percentage</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Charge Type Value</label>
                                                <input type="number" step="0.01" class="form-control" value="{{ $charge->value }}" data-field="value">
                                            </div>
                                            <div class="col-md-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary me-1 update-charge" data-charge-id="{{ $charge->id }}">
                                                    <i class="mdi mdi-check"></i> Update
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-charge" data-charge-id="{{ $charge->id }}">
                                                    <i class="mdi mdi-delete"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    @empty
                                    <div class="text-center py-4 text-muted">
                                        <i class="mdi mdi-information-outline mdi-48px"></i>
                                        <p>No charges added yet. Click "Add Product Charge" to get started.</p>
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Charge Modal -->
<div class="modal fade" id="addChargeModal" tabindex="-1" aria-labelledby="addChargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background-color: white;">
            <div class="modal-header" style="background-color: #0d6efd; border-bottom: 1px solid #dee2e6;">
                <h5 class="modal-title" style="color: #fff;" id="addChargeModalLabel">Add Product Charge</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addChargeForm">
                @csrf
                <div class="modal-body" style="background-color: white;">
                    <div class="form-group mb-3">
                        <label for="charge_name" class="form-label" style="color: #000;">Charge Name</label>
                        <input type="text" class="form-control" id="charge_name" name="name" required style="background-color: white; color: #000;">
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="charge_type" class="form-label" style="color: #000;">Charge Type</label>
                        <select class="form-control" id="charge_type" name="type" required style="background-color: white; color: #000;">
                            <option value="">Select Type</option>
                            <option value="1">Fixed Amount</option>
                            <option value="2">Percentage</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="charge_value" class="form-label" style="color: #000;">Charge Value</label>
                        <input type="number" step="0.01" class="form-control" id="charge_value" name="value" required style="background-color: white; color: #000;">
                    </div>
                </div>
                <div class="modal-footer" style="background-color: white; border-top: 1px solid #dee2e6;">
                    <button type="submit" class="btn btn-primary">Add Charge</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const productId = {{ $product->id }};
const accountSelectElement = document.getElementById('fees_account');

if (accountSelectElement) {
    console.log('Fees account select detected:', accountSelectElement.value);
    accountSelectElement.addEventListener('change', function() {
        console.log('Fees account changed to:', this.value);
    });
} else {
    console.warn('Fees account select element not found in DOM');
}

// Update Product Details
document.getElementById('productDetailsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const accountSelect = document.getElementById('fees_account');
    const accountValue = accountSelect ? accountSelect.value : formData.get('account');
    
    console.log('Account value from form:', formData.get('account'));
    console.log('Account value from select element:', accountValue);
    if (accountSelect) {
        const selectedOption = accountSelect.options[accountSelect.selectedIndex];
        console.log('Selected option details:', selectedOption ? {
            value: selectedOption.value,
            text: selectedOption.text,
            dataset: selectedOption.dataset
        } : 'No option selected');
    }
    
    // Validate account field
    if (!accountValue || accountValue === '' || accountValue === null) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please select a Fees Account'
        });
        return;
    }
    
    const accountInt = parseInt(accountValue);
    if (isNaN(accountInt)) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Invalid account value selected'
        });
        return;
    }
    
    const data = {
        name: formData.get('name'),
        loan_type: parseInt(formData.get('loan_type')),
        type: parseInt(formData.get('type')),
        period_type: parseInt(formData.get('period_type')),
        description: formData.get('description'),
        account: accountInt,
        isactive: {{ $product->isactive ?? 1 }},
        max_amt: {{ $product->max_amt ?? 0 }},
        interest: {{ $product->interest ?? 0 }},
        cash_sceurity: {{ $product->cash_sceurity ?? 0 }}
    };
    
    console.log('Data being sent:', data);
    updateProduct(data, 'Product details updated successfully');
});

// Update Product Parameters
document.getElementById('productParametersForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const nameInput = document.getElementById('name');
    const loanTypeInput = document.getElementById('loan_type');
    const productTypeInput = document.getElementById('type');
    const periodTypeInput = document.getElementById('period_type');
    const descriptionInput = document.getElementById('description');
    const accountInput = accountSelectElement;

    const data = {
        name: nameInput ? nameInput.value : '{{ addslashes($product->name) }}',
        loan_type: loanTypeInput && loanTypeInput.value ? parseInt(loanTypeInput.value) : {{ $product->loan_type }},
        type: productTypeInput && productTypeInput.value ? parseInt(productTypeInput.value) : {{ $product->type }},
        period_type: periodTypeInput && periodTypeInput.value ? parseInt(periodTypeInput.value) : {{ $product->period_type }},
        description: descriptionInput ? descriptionInput.value : '{{ addslashes($product->description) }}',
        account: accountInput && accountInput.value ? parseInt(accountInput.value) : {{ $product->account }},
        isactive: {{ $product->isactive }},
        max_amt: formData.get('max_amt'),
        interest: formData.get('interest'),
        cash_sceurity: formData.get('cash_sceurity')
    };
    
    updateProduct(data, 'Product parameters updated successfully');
});

function updateProduct(data, successMessage) {
    console.log('Sending request to:', `/admin/products/${productId}`);
    console.log('Request data:', data);
    
    fetch(`/admin/products/${productId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json().then(jsonData => ({
            status: response.status,
            data: jsonData
        }));
    })
    .then(({status, data}) => {
        console.log('Response data:', data);
        
        if (status === 200 && data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: successMessage,
                showConfirmButton: false,
                timer: 1500
            });
        } else if (status === 422) {
            // Validation errors
            let errorMsg = 'Validation failed:\n';
            if (data.errors) {
                errorMsg += Object.values(data.errors).flat().join('\n');
            } else {
                errorMsg = data.message || 'Validation failed';
            }
            console.error('Validation errors:', data.errors);
            Swal.fire({
                icon: 'error',
                title: 'Validation Error!',
                text: errorMsg
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message || 'Failed to update product'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while updating the product'
        });
    });
}

// Add Charge Button
document.getElementById('addChargeBtn').addEventListener('click', function() {
    new bootstrap.Modal(document.getElementById('addChargeModal')).show();
});

// Add Charge Form
document.getElementById('addChargeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        product_id: productId,
        name: formData.get('name'),
        type: formData.get('type'),
        value: formData.get('value'),
        isactive: 1
    };
    
    fetch('/admin/product-charges', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Charge added successfully',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message || 'Failed to add charge'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'An error occurred while adding the charge'
        });
    });
});

// Update Charge
document.querySelectorAll('.update-charge').forEach(button => {
    button.addEventListener('click', function() {
        const chargeId = this.getAttribute('data-charge-id');
        const row = document.querySelector(`.charge-row[data-charge-id="${chargeId}"]`);
        
        const data = {
            name: row.querySelector('[data-field="name"]').value,
            type: row.querySelector('[data-field="type"]').value,
            value: row.querySelector('[data-field="value"]').value
        };
        
        fetch(`/admin/product-charges/${chargeId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Charge updated successfully',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to update charge'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while updating the charge'
            });
        });
    });
});

// Delete Charge
document.querySelectorAll('.delete-charge').forEach(button => {
    button.addEventListener('click', function() {
        const chargeId = this.getAttribute('data-charge-id');
        const row = document.querySelector(`.charge-row[data-charge-id="${chargeId}"]`);
        const chargeName = row.querySelector('[data-field="name"]').value;
        
        Swal.fire({
            title: 'Are you sure?',
            text: `Delete charge "${chargeName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/admin/product-charges/${chargeId}`, {
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
                            text: 'Charge deleted successfully',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Failed to delete charge'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while deleting the charge'
                    });
                });
            }
        });
    });
});

// Format amount inputs with thousand separators
document.addEventListener('DOMContentLoaded', function() {
    // Function to format amount inputs
    function setupAmountInput(displayId, hiddenId, fieldName) {
        const displayInput = document.getElementById(displayId);
        const hiddenInput = document.getElementById(hiddenId);
        
        if (displayInput && hiddenInput) {
            // Format on input
            displayInput.addEventListener('input', function(e) {
                // Remove all non-digit characters
                let value = e.target.value.replace(/[^\d]/g, '');
                
                // Update hidden input with raw value
                hiddenInput.value = value;
                
                // Format with commas
                if (value) {
                    e.target.value = parseInt(value).toLocaleString();
                } else {
                    e.target.value = '';
                }
            });
            
            // Prevent form submission if value is invalid
            displayInput.closest('form').addEventListener('submit', function(e) {
                const rawValue = hiddenInput.value;
                if (displayInput.hasAttribute('required') && (!rawValue || rawValue === '0')) {
                    e.preventDefault();
                    displayInput.focus();
                    alert('Please enter a valid ' + fieldName);
                }
            });
        }
    }
    
    // Setup formatter only for max loan amount
    setupAmountInput('max_amt_display', 'max_amt', 'max loan amount');
});
</script>
@endpush
