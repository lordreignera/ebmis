@if(isset($loans) && $loans->count() > 0)
    <div class="btn-group">
        <a href="{{ request()->fullUrlWithQuery(['download' => 'csv']) }}" class="btn btn-sm btn-outline-success">
            <i class="mdi mdi-file-delimited"></i> CSV
        </a>
        <a href="{{ request()->fullUrlWithQuery(['download' => 'excel']) }}" class="btn btn-sm btn-outline-primary">
            <i class="mdi mdi-file-excel"></i> Excel
        </a>
        <a href="{{ request()->fullUrlWithQuery(['download' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">
            <i class="mdi mdi-file-pdf"></i> PDF
        </a>
    </div>
@endif