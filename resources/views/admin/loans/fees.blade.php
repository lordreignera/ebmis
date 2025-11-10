@extends('admin.layout')

@section('title', 'Fee Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-money-check-alt"></i>
                        Fee Management
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#fee-calculation-modal">
                            <i class="fas fa-calculator"></i> Calculate Fees
                        </button>
                        <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#create-fee-type-modal">
                            <i class="fas fa-plus"></i> Create Fee Type
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $statistics['total_fee_types'] }}</h3>
                                    <p>Active Fee Types</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-list"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ number_format($statistics['total_fees_collected'], 0) }}</h3>
                                    <p>Total Fees Collected (UGX)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ number_format($statistics['fees_this_month'], 0) }}</h3>
                                    <p>This Month (UGX)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $statistics['active_product_charges'] }}</h3>
                                    <p>Active Product Charges</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fee Types Management -->
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Fee Types</h5>
                        </div>
                        <div class="card-body">
                            @if(count($fee_types) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="fee-types-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($fee_types as $feeType)
                                        <tr>
                                            <td>{{ $feeType->name }}</td>
                                            <td>{{ $feeType->description ?: 'N/A' }}</td>
                                            <td>
                                                @if($feeType->active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($feeType->created_at)->format('d-M-Y') }}</td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm edit-fee-type-btn" 
                                                        data-id="{{ $feeType->id }}"
                                                        data-name="{{ $feeType->name }}"
                                                        data-description="{{ $feeType->description }}"
                                                        data-active="{{ $feeType->active }}">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-info btn-sm view-charges-btn" 
                                                        data-fee-type-id="{{ $feeType->id }}"
                                                        data-fee-type-name="{{ $feeType->name }}">
                                                    <i class="fas fa-eye"></i> View Charges
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No fee types found. Create fee types to manage product charges.
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Product Charges -->
                    <div class="card card-outline card-success mb-4">
                        <div class="card-header">
                            <h5 class="card-title">Product Charges</h5>
                            <div class="card-tools">
                                <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#create-charge-modal">
                                    <i class="fas fa-plus"></i> Add Charge
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(count($product_charges) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered" id="product-charges-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Fee Type</th>
                                            <th>Charge Type</th>
                                            <th>Amount/Rate</th>
                                            <th>Mandatory</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($product_charges as $charge)
                                        <tr>
                                            <td>{{ $charge->product->name ?? 'N/A' }}</td>
                                            <td>{{ $charge->feeType->name ?? 'N/A' }}</td>
                                            <td>
                                                @if($charge->charge_type === 'deducted')
                                                    <span class="badge badge-warning">Deducted</span>
                                                @else
                                                    <span class="badge badge-primary">Upfront</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($charge->amount_type === 'percentage')
                                                    {{ $charge->amount }}%
                                                @else
                                                    UGX {{ number_format($charge->amount, 2) }}
                                                @endif
                                                @if($charge->min_amount)
                                                    <br><small>Min: UGX {{ number_format($charge->min_amount, 2) }}</small>
                                                @endif
                                                @if($charge->max_amount)
                                                    <br><small>Max: UGX {{ number_format($charge->max_amount, 2) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($charge->mandatory)
                                                    <span class="badge badge-danger">Mandatory</span>
                                                @else
                                                    <span class="badge badge-secondary">Optional</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($charge->active)
                                                    <span class="badge badge-success">Active</span>
                                                @else
                                                    <span class="badge badge-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-warning btn-sm edit-charge-btn" 
                                                        data-id="{{ $charge->id }}"
                                                        data-charge="{{ json_encode($charge) }}">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm delete-charge-btn" 
                                                        data-id="{{ $charge->id }}"
                                                        data-product="{{ $charge->product->name ?? 'N/A' }}"
                                                        data-fee-type="{{ $charge->feeType->name ?? 'N/A' }}">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                No product charges found. Add charges to your products to enable fee calculation.
                            </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fee Calculation Modal -->
<div class="modal fade" id="fee-calculation-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Calculate Loan Fees</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="fee-calculation-form">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="calc_product_id">Product *</label>
                                <select class="form-control" name="product_id" id="calc_product_id" required>
                                    <option value="">Select product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="calc_principal">Principal Amount *</label>
                                <input type="number" class="form-control" name="principal" id="calc_principal" 
                                       step="0.01" min="1000" required placeholder="Enter loan principal">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fee Breakdown -->
                    <div id="fee-breakdown" style="display: none;">
                        <h6 class="mt-4">Fee Breakdown:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fee Type</th>
                                        <th>Charge Type</th>
                                        <th>Rate/Amount</th>
                                        <th>Calculated Amount</th>
                                        <th>Mandatory</th>
                                    </tr>
                                </thead>
                                <tbody id="fee-breakdown-body">
                                    <!-- Fee breakdown will be populated here -->
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <th colspan="3">Total Fees</th>
                                        <th id="total-fees">UGX 0</th>
                                        <th></th>
                                    </tr>
                                    <tr class="table-success">
                                        <th colspan="3">Disbursement Amount</th>
                                        <th id="disbursement-amount">UGX 0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calculator"></i> Calculate Fees
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Fee Type Modal -->
<div class="modal fade" id="create-fee-type-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">Create Fee Type</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="create-fee-type-form">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="fee_type_name">Name *</label>
                        <input type="text" class="form-control" name="name" id="fee_type_name" required 
                               placeholder="e.g., Processing Fee, Service Charge">
                    </div>
                    
                    <div class="form-group">
                        <label for="fee_type_description">Description</label>
                        <textarea class="form-control" name="description" id="fee_type_description" rows="3" 
                                  placeholder="Brief description of this fee type..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="active" id="fee_type_active" value="1" checked>
                            <label class="form-check-label" for="fee_type_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Create Fee Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Fee Type Modal -->
<div class="modal fade" id="edit-fee-type-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">Edit Fee Type</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="edit-fee-type-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="fee_type_id" id="edit_fee_type_id">
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_fee_type_name">Name *</label>
                        <input type="text" class="form-control" name="name" id="edit_fee_type_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_fee_type_description">Description</label>
                        <textarea class="form-control" name="description" id="edit_fee_type_description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="active" id="edit_fee_type_active" value="1">
                            <label class="form-check-label" for="edit_fee_type_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Fee Type
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Product Charge Modal -->
<div class="modal fade" id="create-charge-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">Add Product Charge</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="create-charge-form">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="charge_product_id">Product *</label>
                                <select class="form-control" name="product_id" id="charge_product_id" required>
                                    <option value="">Select product</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="charge_fee_type_id">Fee Type *</label>
                                <select class="form-control" name="fee_type_id" id="charge_fee_type_id" required>
                                    <option value="">Select fee type</option>
                                    @foreach($fee_types as $feeType)
                                        @if($feeType->active)
                                            <option value="{{ $feeType->id }}">{{ $feeType->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="charge_type">Charge Type *</label>
                                <select class="form-control" name="charge_type" id="charge_type" required>
                                    <option value="">Select charge type</option>
                                    <option value="deducted">Deducted from Principal</option>
                                    <option value="upfront">Paid Upfront</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount_type">Amount Type *</label>
                                <select class="form-control" name="amount_type" id="amount_type" required>
                                    <option value="">Select amount type</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="charge_amount">Amount/Rate *</label>
                                <input type="number" class="form-control" name="amount" id="charge_amount" 
                                       step="0.01" min="0" required>
                                <small class="form-text text-muted" id="amount-help">Enter percentage (e.g., 2.5) or fixed amount</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="min_amount">Minimum Amount</label>
                                <input type="number" class="form-control" name="min_amount" id="min_amount" 
                                       step="0.01" min="0" placeholder="Optional">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="max_amount">Maximum Amount</label>
                                <input type="number" class="form-control" name="max_amount" id="max_amount" 
                                       step="0.01" min="0" placeholder="Optional">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="mandatory" id="charge_mandatory" value="1">
                                    <label class="form-check-label" for="charge_mandatory">
                                        Mandatory Charge
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="active" id="charge_active" value="1" checked>
                                    <label class="form-check-label" for="charge_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Add Charge
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#fee-types-table, #product-charges-table').DataTable({
        responsive: true,
        pageLength: 25
    });
    
    // Handle amount type change for charge creation
    $('#amount_type').on('change', function() {
        const type = $(this).val();
        if (type === 'percentage') {
            $('#amount-help').text('Enter percentage (e.g., 2.5 for 2.5%)');
        } else {
            $('#amount-help').text('Enter fixed amount (e.g., 5000)');
        }
    });
    
    // Fee calculation
    $('#fee-calculation-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Calculating...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.fees.calculate") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    displayFeeBreakdown(response.fees, response.totals);
                    $('#fee-breakdown').show();
                } else {
                    toastr.error(response.message || 'Fee calculation failed');
                }
                submitBtn.html(originalText).prop('disabled', false);
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'An error occurred during calculation';
                toastr.error(errorMsg);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    function displayFeeBreakdown(fees, totals) {
        let html = '';
        fees.forEach(fee => {
            html += `<tr>
                <td>${fee.fee_type_name}</td>
                <td><span class="badge badge-${fee.charge_type === 'deducted' ? 'warning' : 'primary'}">${fee.charge_type}</span></td>
                <td>${fee.amount_type === 'percentage' ? fee.amount + '%' : 'UGX ' + new Intl.NumberFormat().format(fee.amount)}</td>
                <td>UGX ${new Intl.NumberFormat().format(fee.calculated_amount)}</td>
                <td>${fee.mandatory ? '<span class="badge badge-danger">Yes</span>' : '<span class="badge badge-secondary">No</span>'}</td>
            </tr>`;
        });
        
        $('#fee-breakdown-body').html(html);
        $('#total-fees').text('UGX ' + new Intl.NumberFormat().format(totals.total_fees));
        $('#disbursement-amount').text('UGX ' + new Intl.NumberFormat().format(totals.disbursement_amount));
    }
    
    // Create fee type
    $('#create-fee-type-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Creating...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.fees.fee-types.create") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Fee type created successfully!');
                    $('#create-fee-type-modal').modal('hide');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message || 'Fee type creation failed');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'An error occurred while creating fee type';
                toastr.error(errorMsg);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Edit fee type button
    $('.edit-fee-type-btn').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const description = $(this).data('description');
        const active = $(this).data('active');
        
        $('#edit_fee_type_id').val(id);
        $('#edit_fee_type_name').val(name);
        $('#edit_fee_type_description').val(description);
        $('#edit_fee_type_active').prop('checked', active);
        
        $('#edit-fee-type-modal').modal('show');
    });
    
    // Update fee type
    $('#edit-fee-type-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        const feeTypeId = $('#edit_fee_type_id').val();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.fees.fee-types.update", ":id") }}'.replace(':id', feeTypeId),
            type: 'PUT',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Fee type updated successfully!');
                    $('#edit-fee-type-modal').modal('hide');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message || 'Fee type update failed');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'An error occurred while updating fee type';
                toastr.error(errorMsg);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Create product charge
    $('#create-charge-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Adding...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.loan-management.fees.charges.create") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Charge added successfully!');
                    $('#create-charge-modal').modal('hide');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    toastr.error(response.message || 'Charge creation failed');
                    submitBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'An error occurred while adding charge';
                toastr.error(errorMsg);
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // View charges for fee type
    $('.view-charges-btn').on('click', function() {
        const feeTypeId = $(this).data('fee-type-id');
        const feeTypeName = $(this).data('fee-type-name');
        
        // Filter the product charges table
        const table = $('#product-charges-table').DataTable();
        table.column(1).search(feeTypeName).draw();
        
        toastr.info('Filtered charges for: ' + feeTypeName);
    });
    
    // Reset modal forms when closed
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('#fee-breakdown').hide();
    });
});
</script>
@endpush