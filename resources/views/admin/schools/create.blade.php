@extends('layouts.admin')

@section('title', 'Add School')

@section('content')
<div class="container-fluid">
    @include('admin.partials.page-header', [
        'title' => 'Add School',
        'subtitle' => 'Create the school record and its first school administrator account.',
        'icon' => 'mdi mdi-school',
        'backFallback' => route('admin.schools.index'),
        'backLabel' => 'Back to Schools',
    ])

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.schools.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">School name</label><input name="school_name" class="form-control" value="{{ old('school_name') }}" required></div>
                <div class="col-md-3"><label class="form-label">School code</label><input name="school_code" class="form-control" value="{{ old('school_code') }}" placeholder="Generated if blank"></div>
                <div class="col-md-3"><label class="form-label">Registration number</label><input name="registration_number" class="form-control" value="{{ old('registration_number') }}"></div>
                <div class="col-md-4">
                    <label class="form-label">School type</label>
                    <select name="school_type" class="form-select" required>
                        @foreach(['Primary', 'Secondary', 'Primary & Secondary', 'Nursery', 'University', 'College', 'Other'] as $type)<option value="{{ $type }}" @selected(old('school_type') === $type)>{{ $type }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ownership</label>
                    <select name="ownership" class="form-select" required>
                        @foreach(['Government', 'Private', 'Religious', 'Community', 'NGO'] as $ownership)<option value="{{ $ownership }}" @selected(old('ownership', 'Private') === $ownership)>{{ $ownership }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Initial status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" @selected(old('status') === 'pending')>Pending approval</option>
                        <option value="approved" @selected(old('status') === 'approved')>Approved and active</option>
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">School administrator</label><input name="contact_person" class="form-control" value="{{ old('contact_person') }}" required></div>
                <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone') }}" required></div>
                <div class="col-md-8"><label class="form-label">Physical address</label><input name="physical_address" class="form-control" value="{{ old('physical_address') }}" required></div>
                <div class="col-md-4"><label class="form-label">District</label><input name="district" class="form-control" value="{{ old('district') }}" required></div>
                <div class="col-md-6"><label class="form-label">Temporary password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
                <div class="col-md-6"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required minlength="8"></div>
            </div>
            <div class="mt-4">
                <button class="btn btn-primary"><i class="mdi mdi-school me-1"></i> Create School</button>
                <a href="{{ route('admin.schools.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></div>
</div>
@endsection
