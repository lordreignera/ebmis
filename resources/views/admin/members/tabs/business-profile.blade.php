<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="mdi mdi-briefcase"></i> Business Information</h5>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBusinessModal">
        <i class="mdi mdi-plus"></i> Add Business
    </button>
</div>

@if($member->businesses->count() > 0)
    @foreach($member->businesses as $business)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ $business->name }}</strong>
                <div>
                    <button type="button" class="btn btn-sm btn-warning" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editBusinessModal"
                            data-business-id="{{ $business->id }}"
                            data-business-data='@json($business)'>
                        <i class="mdi mdi-pencil"></i> Edit
                    </button>
                    <form action="{{ route('admin.members.businesses.destroy', [$member, $business]) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this business?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="mdi mdi-delete"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td><strong>Business Type:</strong></td>
                                <td>{{ $business->businessType->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Registration No:</strong></td>
                                <td>{{ $business->reg_no ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Registration Date:</strong></td>
                                <td>{{ $business->reg_date ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <td><strong>TIN:</strong></td>
                                <td>{{ $business->tin ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Products/Services:</h6>
                        <ul>
                            @if($business->pdt_1)<li>{{ $business->pdt_1 }}</li>@endif
                            @if($business->pdt_2)<li>{{ $business->pdt_2 }}</li>@endif
                            @if($business->pdt_3)<li>{{ $business->pdt_3 }}</li>@endif
                        </ul>
                        
                        @if($business->address)
                            <h6 class="mt-3">Business Address:</h6>
                            <address>
                                {{ $business->address->full_address }}<br>
                                @if($business->address->tel_no)Tel: {{ $business->address->tel_no }}<br>@endif
                                @if($business->address->mobile_no)Mobile: {{ $business->address->mobile_no }}<br>@endif
                                @if($business->address->email)Email: {{ $business->address->email }}@endif
                            </address>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@else
    <div class="alert alert-info">
        <i class="mdi mdi-information"></i> No business information recorded. Click "Add Business" to register business details.
    </div>
@endif

<!-- All modals moved to main show.blade.php outside tab content to fix blinking issue -->
