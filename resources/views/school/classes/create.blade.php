@extends('layouts.admin')

@section('title', 'Create New Class')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h4 mb-1" style="color: #000000;">
                        <i class="mdi mdi-google-classroom me-2"></i>Create New Class
                    </h2>
                    <p class="text-muted mb-0">Add a new class to your school</p>
                </div>
                <div>
                    <a href="{{ route('school.classes.index') }}" class="btn btn-outline-secondary">
                        <i class="mdi mdi-arrow-left me-1"></i>Back to Classes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('school.classes.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- Class Name -->
                            <div class="col-md-6 mb-3">
                                <label for="class_name" class="form-label" style="color: #000000;">
                                    Class Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('class_name') is-invalid @enderror" 
                                       id="class_name" 
                                       name="class_name" 
                                       value="{{ old('class_name') }}" 
                                       placeholder="e.g., Senior One"
                                       required>
                                @error('class_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Class Code -->
                            <div class="col-md-6 mb-3">
                                <label for="class_code" class="form-label" style="color: #000000;">
                                    Class Code
                                </label>
                                <input type="text" 
                                       class="form-control @error('class_code') is-invalid @enderror" 
                                       id="class_code" 
                                       name="class_code" 
                                       value="{{ old('class_code') }}" 
                                       placeholder="e.g., S1A">
                                @error('class_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Level -->
                            <div class="col-md-4 mb-3">
                                <label for="level" class="form-label" style="color: #000000;">
                                    Level <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('level') is-invalid @enderror" 
                                        id="level" 
                                        name="level" 
                                        required>
                                    <option value="">Select Level</option>
                                    <option value="P1" {{ old('level') == 'P1' ? 'selected' : '' }}>Primary 1</option>
                                    <option value="P2" {{ old('level') == 'P2' ? 'selected' : '' }}>Primary 2</option>
                                    <option value="P3" {{ old('level') == 'P3' ? 'selected' : '' }}>Primary 3</option>
                                    <option value="P4" {{ old('level') == 'P4' ? 'selected' : '' }}>Primary 4</option>
                                    <option value="P5" {{ old('level') == 'P5' ? 'selected' : '' }}>Primary 5</option>
                                    <option value="P6" {{ old('level') == 'P6' ? 'selected' : '' }}>Primary 6</option>
                                    <option value="P7" {{ old('level') == 'P7' ? 'selected' : '' }}>Primary 7</option>
                                    <option value="S1" {{ old('level') == 'S1' ? 'selected' : '' }}>Senior 1</option>
                                    <option value="S2" {{ old('level') == 'S2' ? 'selected' : '' }}>Senior 2</option>
                                    <option value="S3" {{ old('level') == 'S3' ? 'selected' : '' }}>Senior 3</option>
                                    <option value="S4" {{ old('level') == 'S4' ? 'selected' : '' }}>Senior 4</option>
                                    <option value="S5" {{ old('level') == 'S5' ? 'selected' : '' }}>Senior 5</option>
                                    <option value="S6" {{ old('level') == 'S6' ? 'selected' : '' }}>Senior 6</option>
                                </select>
                                @error('level')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Stream -->
                            <div class="col-md-4 mb-3">
                                <label for="stream" class="form-label" style="color: #000000;">
                                    Stream
                                </label>
                                <input type="text" 
                                       class="form-control @error('stream') is-invalid @enderror" 
                                       id="stream" 
                                       name="stream" 
                                       value="{{ old('stream') }}" 
                                       placeholder="e.g., A, B, East">
                                @error('stream')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Capacity -->
                            <div class="col-md-4 mb-3">
                                <label for="capacity" class="form-label" style="color: #000000;">
                                    Student Capacity <span class="text-danger">*</span>
                                </label>
                                <input type="number" 
                                       class="form-control @error('capacity') is-invalid @enderror" 
                                       id="capacity" 
                                       name="capacity" 
                                       value="{{ old('capacity', 40) }}" 
                                       min="1" 
                                       placeholder="40"
                                       required>
                                @error('capacity')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Class Teacher -->
                            <div class="col-md-6 mb-3">
                                <label for="class_teacher_id" class="form-label" style="color: #000000;">
                                    Class Teacher
                                </label>
                                <select class="form-select @error('class_teacher_id') is-invalid @enderror" 
                                        id="class_teacher_id" 
                                        name="class_teacher_id">
                                    <option value="">Select Teacher (Optional)</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" {{ old('class_teacher_id') == $teacher->id ? 'selected' : '' }}>
                                            {{ $teacher->full_name }} ({{ $teacher->staff_id }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('class_teacher_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">You can assign a teacher later</small>
                            </div>

                            <!-- Academic Year -->
                            <div class="col-md-6 mb-3">
                                <label for="academic_year" class="form-label" style="color: #000000;">
                                    Academic Year
                                </label>
                                <input type="text" 
                                       class="form-control @error('academic_year') is-invalid @enderror" 
                                       id="academic_year" 
                                       name="academic_year" 
                                       value="{{ old('academic_year', date('Y')) }}" 
                                       placeholder="2025">
                                @error('academic_year')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Description -->
                            <div class="col-12 mb-3">
                                <label for="description" class="form-label" style="color: #000000;">
                                    Description
                                </label>
                                <textarea class="form-control @error('description') is-invalid @enderror" 
                                          id="description" 
                                          name="description" 
                                          rows="3" 
                                          placeholder="Additional information about this class...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label" style="color: #000000;">
                                    Status
                                </label>
                                <select class="form-select @error('status') is-invalid @enderror" 
                                        id="status" 
                                        name="status">
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="archived" {{ old('status') == 'archived' ? 'selected' : '' }}>Archived</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-content-save me-1"></i>Create Class
                            </button>
                            <a href="{{ route('school.classes.index') }}" class="btn btn-light">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
