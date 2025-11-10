@extends('layouts.admin')

@section('title', 'Create New Group')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Create New Group</h1>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left"></i> Back to Groups
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Group Information</h4>
                    <p class="card-description">Fill in the group details below</p>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.groups.store') }}" method="POST" id="groupForm">
                        @csrf
                        
                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3 text-primary">Basic Information</h5>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="group_code_preview">Group Code</label>
                                    <input type="text" class="form-control" id="group_code_preview" 
                                           value="BIMS{{ time() }}" readonly style="background-color: #f8f9fa;">
                                    <small class="form-text text-muted">Auto-generated code (preview)</small>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="name" class="required">Group Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" required 
                                           placeholder="Enter group name">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="inception_date" class="required">Inception Date</label>
                                    <input type="date" class="form-control @error('inception_date') is-invalid @enderror" 
                                           id="inception_date" name="inception_date" value="{{ old('inception_date') }}" required>
                                    @error('inception_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="branch_id" class="required">Branch</label>
                                    <select class="form-control @error('branch_id') is-invalid @enderror" 
                                            id="branch_id" name="branch_id" required>
                                        <option value="">Select Branch</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('branch_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sector" class="required">Sector</label>
                                    <select class="form-control @error('sector') is-invalid @enderror" 
                                            id="sector" name="sector" required>
                                        <option value="">Select Sector</option>
                                        <option value="Agriculture" {{ old('sector') == 'Agriculture' ? 'selected' : '' }}>Agriculture</option>
                                        <option value="Industry" {{ old('sector') == 'Industry' ? 'selected' : '' }}>Industry</option>
                                        <option value="Education" {{ old('sector') == 'Education' ? 'selected' : '' }}>Education</option>
                                    </select>
                                    @error('sector')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="type" class="required">Group Type</label>
                                    <select class="form-control @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">Select Type</option>
                                        <option value="1" {{ old('type') == '1' ? 'selected' : '' }}>Preliminary (Open)</option>
                                        <option value="2" {{ old('type') == '2' ? 'selected' : '' }}>Incubation (Closed)</option>
                                    </select>
                                    <small class="form-text text-muted">Open groups can accept new members</small>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3 text-primary">Address Information</h5>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="village">Village</label>
                                    <input type="text" class="form-control @error('village') is-invalid @enderror" 
                                           id="village" name="village" value="{{ old('village') }}" 
                                           placeholder="Enter village">
                                    @error('village')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="parish">Parish</label>
                                    <input type="text" class="form-control @error('parish') is-invalid @enderror" 
                                           id="parish" name="parish" value="{{ old('parish') }}" 
                                           placeholder="Enter parish">
                                    @error('parish')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="subcounty">Subcounty</label>
                                    <input type="text" class="form-control @error('subcounty') is-invalid @enderror" 
                                           id="subcounty" name="subcounty" value="{{ old('subcounty') }}" 
                                           placeholder="Enter subcounty">
                                    @error('subcounty')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="district">District</label>
                                    <input type="text" class="form-control @error('district') is-invalid @enderror" 
                                           id="district" name="district" value="{{ old('district') }}" 
                                           placeholder="Enter district">
                                    @error('district')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Member Selection -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3 text-primary">Add Members (Optional)</h5>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="alert alert-info d-flex align-items-center" role="alert">
                                    <i class="mdi mdi-information-outline me-2"></i>
                                    <div>
                                        Select approved members to add to this group. You can also add members later after creating the group.
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="member_ids">Select Members</label>
                                    <select class="form-control @error('member_ids') is-invalid @enderror" 
                                            id="member_ids" name="member_ids[]" multiple style="height: 200px;">
                                        @if($members->count() > 0)
                                            @foreach($members as $member)
                                                <option value="{{ $member->id }}" 
                                                        {{ in_array($member->id, old('member_ids', [])) ? 'selected' : '' }}>
                                                    {{ $member->fname }} {{ $member->lname }} 
                                                    ({{ $member->code }}) - {{ $member->contact }} - {{ $member->branch->name ?? 'No Branch' }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option disabled>No approved members available without groups</option>
                                        @endif
                                    </select>
                                    <small class="form-text text-muted">
                                        Hold Ctrl/Cmd to select multiple members. Only approved members without groups are shown.
                                    </small>
                                    @error('member_ids')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                @if($members->count() == 0)
                                    <div class="text-center py-3">
                                        <i class="mdi mdi-account-off mdi-48px text-muted"></i>
                                        <p class="text-muted mt-2">No approved members available to add to groups.</p>
                                        <small class="text-muted">All approved members are already assigned to groups or no approved members exist.</small>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row">
                            <div class="col-md-12">
                                <hr>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">
                                        <i class="mdi mdi-arrow-left"></i> Back to Groups
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="mdi mdi-check"></i> Create Group
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
$(document).ready(function() {
    // Update group code preview every second
    setInterval(function() {
        $('#group_code_preview').val('BIMS' + Math.floor(Date.now() / 1000));
    }, 1000);
    
    // Initialize Select2 for better member selection
    $('#member_ids').select2({
        placeholder: 'Search and select members...',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endsection
@endsection