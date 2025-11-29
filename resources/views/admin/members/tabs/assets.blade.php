<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="mdi mdi-home"></i> Asset Register</h5>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAssetModal">
        <i class="mdi mdi-plus"></i> Add Asset
    </button>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6>Total Assets Value</h6>
                <h3>UGX {{ number_format($member->total_assets, 0) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h6>Number of Assets</h6>
                <h3>{{ $member->assets->count() }}</h3>
            </div>
        </div>
    </div>
</div>

@if($member->assets->count() > 0)
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Asset Type</th>
                    <th>Business</th>
                    <th>Quantity</th>
                    <th>Unit Value</th>
                    <th>Total Value</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($member->assets as $index => $asset)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $asset->assetType->name ?? 'N/A' }}</td>
                        <td>{{ $asset->business->name ?? 'Personal' }}</td>
                        <td>{{ $asset->quantity }}</td>
                        <td>{{ number_format($asset->value, 0) }}</td>
                        <td><strong>{{ number_format($asset->total_value, 0) }}</strong></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editAssetModal"
                                    data-asset-id="{{ $asset->id }}"
                                    data-asset-type="{{ $asset->asset_type }}"
                                    data-business-id="{{ $asset->business_id ?? '' }}"
                                    data-quantity="{{ $asset->quantity }}"
                                    data-value="{{ $asset->value }}">
                                <i class="mdi mdi-pencil"></i>
                            </button>
                            <form action="{{ route('admin.members.assets.destroy', [$member, $asset]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this asset?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="mdi mdi-delete"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">
        <i class="mdi mdi-information"></i> No assets registered.
    </div>
@endif

<!-- All modals moved to main show.blade.php outside tab content to fix blinking issue -->
