@extends('layouts.admin')

@section('title', 'School Loan Products Settings')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">School Loan Products</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item active">School Loan Products</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Page Header Actions -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="font-weight-bold">Manage School Loan Products</h3>
                        <p class="text-muted mb-0">Configure school, student, and staff loan products</p>
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
                        <h4 class="card-title">School Loan Products ({{ $products->total() }})</h4>
                        <p class="card-description">Products for Schools, Students, and Staff</p>
                        
                        <!-- Search and Filters -->
                        <form method="GET" action="{{ route('admin.settings.school-loan-products') }}" class="mb-4">
                            <div class="row">
                                <div class="col-md-3">
                                    <input type="text" 
                                           name="search" 
                                           class="form-control" 
                                           placeholder="Search products..." 
                                           value="{{ request('search') }}">
                                </div>
                                <div class="col-md-2">
                                    <select name="loan_type" class="form-select">
                                        <option value="">All Types</option>
                                        <option value="4" {{ request('loan_type') == '4' ? 'selected' : '' }}>School</option>
                                        <option value="5" {{ request('loan_type') == '5' ? 'selected' : '' }}>Student</option>
                                        <option value="6" {{ request('loan_type') == '6' ? 'selected' : '' }}>Staff</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="period_type" class="form-select">
                                        <option value="">All Periods</option>
                                        <option value="1" {{ request('period_type') == '1' ? 'selected' : '' }}>Daily</option>
                                        <option value="2" {{ request('period_type') == '2' ? 'selected' : '' }}>Weekly</option>
                                        <option value="3" {{ request('period_type') == '3' ? 'selected' : '' }}>Monthly</option>
                                        <option value="4" {{ request('period_type') == '4' ? 'selected' : '' }}>Yearly</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <select name="status" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <div class="btn-group" role="group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="mdi mdi-filter"></i> Filter
                                        </button>
                                        <a href="{{ route('admin.settings.school-loan-products') }}" class="btn btn-secondary">
                                            <i class="mdi mdi-refresh"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Product Name</th>
                                        <th>Loan Type</th>
                                        <th>Period Type</th>
                                        <th>Interest Rate</th>
                                        <th>Max Amount</th>
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
                                            @if($product->loan_type == 4)
                                                <span class="badge badge-primary">School</span>
                                            @elseif($product->loan_type == 5)
                                                <span class="badge badge-info">Student</span>
                                            @elseif($product->loan_type == 6)
                                                <span class="badge badge-warning">Staff</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $product->loan_type }}</span>
                                            @endif
                                        </td>
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
                                            <span class="badge badge-success">{{ $product->interest }}%</span>
                                        </td>
                                        <td>UGX {{ number_format($product->max_amt) }}</td>
                                        <td>
                                            @if($product->isactive == 1)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-danger">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.loan-products.edit', $product->id) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.loan-products.destroy', $product->id) }}" 
                                                      method="POST" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this product?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger" 
                                                            title="Delete">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="mdi mdi-school mdi-48px"></i>
                                                <h5 class="mt-2">No school loan products found</h5>
                                                <p>Click "Add New Product" to create school, student, or staff loan products</p>
                                                <p class="text-sm">Make sure to set Loan Type to School (4), Student (5), or Staff (6)</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Modern Pagination -->
                        <div class="modern-pagination">
                            <div class="pagination-info">
                                Showing {{ $products->firstItem() ?? 0 }} to {{ $products->lastItem() ?? 0 }} of {{ $products->total() }} entries
                            </div>
                            <div class="pagination-controls">
                                @if ($products->onFirstPage())
                                    <span class="pagination-btn" disabled>
                                        <i class="mdi mdi-chevron-left"></i>
                                        Previous
                                    </span>
                                @else
                                    <a href="{{ $products->appends(request()->except('page'))->previousPageUrl() }}" class="pagination-btn">
                                        <i class="mdi mdi-chevron-left"></i>
                                        Previous
                                    </a>
                                @endif

                                <div class="pagination-numbers">
                                    @php
                                        $currentPage = $products->currentPage();
                                        $lastPage = $products->lastPage();
                                        $start = max(1, $currentPage - 2);
                                        $end = min($lastPage, $currentPage + 2);
                                        
                                        // Adjust if at the beginning or end
                                        if ($currentPage <= 3) {
                                            $end = min(5, $lastPage);
                                        }
                                        if ($currentPage >= $lastPage - 2) {
                                            $start = max(1, $lastPage - 4);
                                        }
                                    @endphp

                                    @if($start > 1)
                                        <a href="{{ $products->url(1) }}" class="pagination-btn">1</a>
                                        @if($start > 2)
                                            <span class="pagination-btn" disabled>...</span>
                                        @endif
                                    @endif

                                    @for ($page = $start; $page <= $end; $page++)
                                        @if ($page == $currentPage)
                                            <span class="pagination-btn active">{{ $page }}</span>
                                        @else
                                            <a href="{{ $products->url($page) }}" class="pagination-btn">{{ $page }}</a>
                                        @endif
                                    @endfor

                                    @if($end < $lastPage)
                                        @if($end < $lastPage - 1)
                                            <span class="pagination-btn" disabled>...</span>
                                        @endif
                                        <a href="{{ $products->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                                    @endif
                                </div>

                                @if ($products->hasMorePages())
                                    <a href="{{ $products->appends(request()->except('page'))->nextPageUrl() }}" class="pagination-btn">
                                        Next
                                        <i class="mdi mdi-chevron-right"></i>
                                    </a>
                                @else
                                    <span class="pagination-btn" disabled>
                                        Next
                                        <i class="mdi mdi-chevron-right"></i>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Success message display
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{{ session('success') }}',
            timer: 3000,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session('error') }}',
            timer: 3000,
            showConfirmButton: false
        });
    @endif
</script>
@endpush
