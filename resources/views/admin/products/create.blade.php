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

        <!-- Form -->
        <div class="row">
            <div class="col-12 grid-margin">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">New Loan Product</h4>
                        <p class="card-description">Fill in the details below to create a new loan product</p>

                        <form action="{{ route('admin.loan-products.store') }}" method="POST" class="forms-sample">
                            @csrf

                            <div class="row">
                                <!-- Product Code (Auto-generated) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="code">Product Code</label>
                                        <input type="text" class="form-control" 
                                               value="Auto-generated (BLN + timestamp)" 
                                               disabled>
                                        <small class="form-text text-muted">Product code will be automatically generated</small>
                                    </div>
                                </div>

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
                            </div>

                            <div class="row">
                                <!-- Product Type -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="type">Product Type <span class="text-danger">*</span></label>
                                        <select class="form-control @error('type') is-invalid @enderror" 
                                                id="type" name="type" required>
                                            <option value="">Select Type</option>
                                            <option value="1" {{ old('type') == 1 ? 'selected' : '' }}>Loan Product</option>
                                            <option value="2" {{ old('type') == 2 ? 'selected' : '' }}>Savings Product</option>
                                            <option value="3" {{ old('type') == 3 ? 'selected' : '' }}>Investment Product</option>
                                        </select>
                                        @error('type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Loan Type -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="loan_type">Loan Type <span class="text-danger">*</span></label>
                                        <select class="form-control @error('loan_type') is-invalid @enderror" 
                                                id="loan_type" name="loan_type" required>
                                            <option value="">Select Loan Type</option>
                                            <option value="1" {{ old('loan_type') == 1 ? 'selected' : '' }}>Individual</option>
                                            <option value="2" {{ old('loan_type') == 2 ? 'selected' : '' }}>Group</option>
                                            <option value="3" {{ old('loan_type') == 3 ? 'selected' : '' }}>Business</option>
                                            <option value="4" {{ old('loan_type') == 4 ? 'selected' : '' }}>Agricultural</option>
                                        </select>
                                        @error('loan_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Maximum Amount -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_amt">Maximum Amount (UGX) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control @error('max_amt') is-invalid @enderror" 
                                               id="max_amt" name="max_amt" value="{{ old('max_amt') }}" 
                                               step="0.01" min="0" placeholder="e.g., 5000000" required>
                                        @error('max_amt')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Interest Rate -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="interest">Interest Rate (%) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control @error('interest') is-invalid @enderror" 
                                               id="interest" name="interest" value="{{ old('interest') }}" 
                                               step="0.01" min="0" max="100" placeholder="e.g., 15" required>
                                        @error('interest')
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
                                            <option value="1" {{ old('period_type') == 1 ? 'selected' : '' }}>Days</option>
                                            <option value="2" {{ old('period_type') == 2 ? 'selected' : '' }}>Weeks</option>
                                            <option value="3" {{ old('period_type') == 3 ? 'selected' : '' }}>Months</option>
                                            <option value="4" {{ old('period_type') == 4 ? 'selected' : '' }}>Years</option>
                                        </select>
                                        @error('period_type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Cash Security (%) -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cash_sceurity">Cash Security (%) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control @error('cash_sceurity') is-invalid @enderror" 
                                               id="cash_sceurity" name="cash_sceurity" value="{{ old('cash_sceurity', 25) }}" 
                                               step="0.01" min="0" max="100" placeholder="e.g., 25" required>
                                        @error('cash_sceurity')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Percentage of loan amount required as cash security</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- System Account -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="account">System Account <span class="text-danger">*</span></label>
                                        <select class="form-control @error('account') is-invalid @enderror" 
                                                id="account" name="account" required>
                                            <option value="">Select Account</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}" {{ old('account') == $acc->id ? 'selected' : '' }}>
                                                    {{ $acc->name }} ({{ $acc->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('account')
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
