@extends('layouts.admin')

@section('title', 'Edit School')

@section('content')
<!-- Header -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h3 class="font-weight-bold" style="color: #000000;">Edit School</h3>
                <p class="text-muted mb-0">{{ $school->school_name }}</p>
            </div>
            <div>
                <a href="{{ route('admin.schools.show', $school) }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left"></i> Back to Details
                </a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle me-2"></i>
        <strong>Validation Error!</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('admin.schools.update', $school) }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Basic Information -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title" style="color: #000000;">Basic Information</h4>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="school_name" class="form-label" style="color: #000000;">School Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="school_name" name="school_name" value="{{ old('school_name', $school->school_name) }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label" style="color: #000000;">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending" {{ $school->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ $school->status == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="suspended" {{ $school->status == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="rejected" {{ $school->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label for="approval_notes" class="form-label" style="color: #000000;">Approval Notes</label>
                            <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3" placeholder="Enter any notes about this school...">{{ old('approval_notes', $school->approval_notes) }}</textarea>
                            <small class="text-muted">These notes are for internal use only</small>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save"></i> Save Changes
                            </button>
                            <a href="{{ route('admin.schools.show', $school) }}" class="btn btn-secondary">
                                <i class="mdi mdi-cancel"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Read-Only Information (for reference) -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title" style="color: #000000;">Contact Information</h4>
                    <table class="table table-borderless">
                        <tr>
                            <th style="color: #000000; width: 40%;">Contact Person:</th>
                            <td>{{ $school->contact_person }}</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Email:</th>
                            <td>{{ $school->email }}</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Phone:</th>
                            <td>{{ $school->phone }}</td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">District:</th>
                            <td>{{ $school->district }}</td>
                        </tr>
                    </table>
                    <small class="text-muted"><i class="mdi mdi-information"></i> Contact details are read-only. School must update from their dashboard.</small>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title" style="color: #000000;">Assessment Status</h4>
                    <table class="table table-borderless">
                        <tr>
                            <th style="color: #000000; width: 40%;">Assessment:</th>
                            <td>
                                @if($school->assessment_complete)
                                    <span class="badge bg-success"><i class="mdi mdi-check"></i> Complete</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="mdi mdi-clock"></i> Incomplete</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th style="color: #000000;">Registration Date:</th>
                            <td>{{ $school->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @if($school->assessment_completed_at)
                        <tr>
                            <th style="color: #000000;">Assessment Completed:</th>
                            <td>{{ $school->assessment_completed_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @endif
                        @if($school->approved_at)
                        <tr>
                            <th style="color: #000000;">Approved On:</th>
                            <td>{{ $school->approved_at->format('d M Y, h:i A') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.card {
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.form-label {
    font-weight: 600;
    color: #333;
}

.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 10px 15px;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn {
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 600;
}

.table-borderless th {
    padding: 8px 0;
}

.table-borderless td {
    padding: 8px 0;
}
</style>
@endsection
