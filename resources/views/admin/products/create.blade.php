@extends('layouts.admin')

@section('title', 'Create Loan Product')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">Create Loan Product</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.loan-products') }}">Loan Products</a></li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Form with Tabs -->
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">New Loan Product</h4>
                        <p class="card-description">Fill in the details below to create a new loan product</p>

                        <!-- Nav Tabs -->
                        <ul class="nav nav-tabs" id="productTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="details-tab" data-bs-toggle="tab" href="#details" 
                                   role="tab" aria-controls="details" aria-selected="true">
                                    <i class="mdi mdi-information-outline"></i> Product Details
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="parameters-tab" data-bs-toggle="tab" href="#parameters" 
                                   role="tab" aria-controls="parameters" aria-selected="false">
                                    <i class="mdi mdi-tune"></i> Product Parameters
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="charges-tab" data-bs-toggle="tab" href="#charges" 
                                   role="tab" aria-controls="charges" aria-selected="false">
                                    <i class="mdi mdi-cash-multiple"></i> Product Upfront Charges
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content mt-4" id="productTabsContent">
                            <!-- Product Details Tab -->
                            <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                                <form action="{{ route('admin.loan-products.store') }}" method="POST" id="detailsForm">
                                    @csrf
                                    
                                    <!-- Hidden fields for parameters with default values -->
                                    <input type="hidden" name="max_amt" value="0">
                                    <input type="hidden" name="interest" value="0">
                                    <input type="hidden" name="cash_sceurity" value="0">

                                    <div class="row">
                                        <!-- Product Name -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">Product Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                       id="name" name="name" value="{{ old('name') }}" 
                                                       placeholder="e.g., Personal Loan" required>
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Fees Account -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="account">Fees Account <span class="text-danger">*</span></label>
                                                <select class="form-control @error('account') is-invalid @enderror" 
                                                        id="account" name="account" required>
                                                    <option value="">Select Fees Account</option>
                                                    @foreach($accounts as $acc)
                                                        @php
                                                            $accId = $acc->Id ?? $acc->id;
                                                        @endphp
                                                        <option value="{{ $accId }}" {{ old('account') == $accId ? 'selected' : '' }}>
                                                            {{ $acc->code }} - {{ $acc->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('account')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Loan Type -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="loan_type">Loan Type <span class="text-danger">*</span></label>
                                                <select class="form-control @error('loan_type') is-invalid @enderror" 
                                                        id="loan_type" name="loan_type" required>
                                                    <option value="">Select Loan Type</option>
                                                    <option value="1" {{ old('loan_type') == 1 ? 'selected' : '' }}>Personal</option>
                                                    <option value="2" {{ old('loan_type') == 2 ? 'selected' : '' }}>Group</option>
                                                    <option value="3" {{ old('loan_type') == 3 ? 'selected' : '' }}>Business</option>
                                                    <option value="4" {{ old('loan_type') == 4 ? 'selected' : '' }}>School</option>
                                                    <option value="5" {{ old('loan_type') == 5 ? 'selected' : '' }}>Student</option>
                                                    <option value="6" {{ old('loan_type') == 6 ? 'selected' : '' }}>Staff</option>
                                                </select>
                                                @error('loan_type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Product Type -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="type">Product Type <span class="text-danger">*</span></label>
                                                <select class="form-control @error('type') is-invalid @enderror" 
                                                        id="type" name="type" required>
                                                    <option value="">Select Type</option>
                                                    <option value="1" {{ old('type') == 1 ? 'selected' : '' }}>Fixed Term</option>
                                                    <option value="2" {{ old('type') == 2 ? 'selected' : '' }}>Revolving</option>
                                                    <option value="3" {{ old('type') == 3 ? 'selected' : '' }}>Emergency</option>
                                                </select>
                                                @error('type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Period Type -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="period_type">Period Type <span class="text-danger">*</span></label>
                                                <select class="form-control @error('period_type') is-invalid @enderror" 
                                                        id="period_type" name="period_type" required>
                                                    <option value="">Select Period Type</option>
                                                    <option value="3" {{ old('period_type') == 3 ? 'selected' : '' }}>Daily</option>
                                                    <option value="1" {{ old('period_type') == 1 ? 'selected' : '' }}>Weekly</option>
                                                    <option value="2" {{ old('period_type') == 2 ? 'selected' : '' }}>Monthly</option>
                                                    <option value="4" {{ old('period_type') == 4 ? 'selected' : '' }}>Yearly</option>
                                                </select>
                                                @error('period_type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <!-- Status -->
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="isactive">Status <span class="text-danger">*</span></label>
                                                <select class="form-control @error('isactive') is-invalid @enderror" 
                                                        id="isactive" name="isactive" required>
                                                    <option value="1" {{ old('isactive', 1) == 1 ? 'selected' : '' }}>Active</option>
                                                    <option value="0" {{ old('isactive') == 0 ? 'selected' : '' }}>Inactive</option>
                                                </select>
                                                @error('isactive')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <!-- Description -->
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="description">Description</label>
                                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                                          id="description" name="description" rows="3" 
                                                          placeholder="Enter product description...">{{ old('description') }}</textarea>
                                                @error('description')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mt-4">
                                        <button type="submit" class="btn btn-primary mr-2">
                                            <i class="mdi mdi-content-save"></i> Create Product
                                        </button>
                                        <a href="{{ route('admin.settings.loan-products') }}" class="btn btn-light">
                                            <i class="mdi mdi-cancel"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <!-- Product Parameters Tab -->
                            <div class="tab-pane fade" id="parameters" role="tabpanel" aria-labelledby="parameters-tab">
                                <div class="alert alert-info">
                                    <i class="mdi mdi-information"></i>
                                    Product parameters can be configured after the product is created.
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="max_amt_preview">Maximum Amount (UGX)</label>
                                            <input type="number" class="form-control" id="max_amt_preview" 
                                                   placeholder="Will be set after creation" disabled>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="interest_preview">Interest (%age per repayment period)</label>
                                            <input type="number" class="form-control" id="interest_preview" 
                                                   placeholder="Will be set after creation" disabled>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="cash_security_preview">Cash Security (%age between 0 and 100)</label>
                                            <input type="number" class="form-control" id="cash_security_preview" 
                                                   placeholder="Will be set after creation" disabled>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-muted">
                                    <i class="mdi mdi-lightbulb-on-outline"></i>
                                    These parameters can be configured by editing the product after creation.
                                </p>
                            </div>

                            <!-- Product Charges Tab -->
                            <div class="tab-pane fade" id="charges" role="tabpanel" aria-labelledby="charges-tab">
                                <div class="alert alert-info">
                                    <i class="mdi mdi-information"></i>
                                    Product charges can be added after the product is created.
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Charge Type</th>
                                                <th>Charge Amount</th>
                                                <th>Charge GL Account</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">
                                                    No charges configured. Add charges after creating the product.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <p class="text-muted">
                                    <i class="mdi mdi-lightbulb-on-outline"></i>
                                    Charges can be added by editing the product after creation.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
