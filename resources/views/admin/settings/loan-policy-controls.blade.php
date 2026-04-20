@extends('layouts.admin')

@section('title', 'Loan Policy Controls')

{{-- DataTables CSS --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
@endpush

@section('content')
<div class="main-panel">
    <div class="content-wrapper">

        <!-- Breadcrumb -->
        <div class="row page-title-header">
            <div class="col-12">
                <div class="page-header">
                    <h4 class="page-title">Loan Scoring Policy Controls</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.settings.dashboard') }}">Settings</a></li>
                        <li class="breadcrumb-item active">Loan Policy Controls</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Page Header -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="font-weight-bold">SDL Scoring Policy Controls</h3>
                        <p class="text-muted mb-0">
                            These values govern the loan scoring engine. Changes take effect immediately on the next score calculation.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="mdi mdi-alert mr-2"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        <!-- Controls Form -->
        <form action="{{ route('admin.settings.loan-policy-controls.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-12 grid-margin stretch-card">
                    <div class="card">
                        <div class="card-body">

                            <div class="alert alert-info mb-4">
                                <i class="mdi mdi-information-outline mr-1"></i>
                                <strong>Percentage fields</strong> are entered as whole numbers (e.g. enter <strong>70</strong> for 70%).
                                <strong>Multiplier fields</strong> are entered as whole numbers (e.g. enter <strong>120</strong> for 1.20×).
                                Score and integer fields are entered directly.
                            </div>

                            <div class="table-responsive">
                                <table id="policyTable" class="table table-bordered table-hover align-middle" style="width:100%">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>Key</th>
                                            <th>Label</th>
                                            <th>Description</th>
                                            <th>Format</th>
                                            <th>Current Value</th>
                                            <th>New Value</th>
                                        </tr>
                                        <tr class="bg-light">
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter key…"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter label…"></th>
                                            <th><input type="text" class="form-control form-control-sm" placeholder="Filter description…"></th>
                                            <th>
                                                <select class="form-control form-control-sm">
                                                    <option value="">All formats</option>
                                                    <option>percent</option>
                                                    <option>multiplier</option>
                                                    <option>score</option>
                                                    <option>integer</option>
                                                </select>
                                            </th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tfoot style="display:none">
                                        <tr><th></th><th></th><th></th><th></th><th></th><th></th></tr>
                                    </tfoot>
                                    <tbody>
                                        @foreach($controls as $control)
                                            @php
                                                // Display value: percent and multiplier × 100 for editing
                                                if (in_array($control->format, ['percent', 'multiplier'])) {
                                                    $editValue = round($control->value * 100, 4);
                                                } else {
                                                    $editValue = (int) $control->value;
                                                }

                                                // Badge color per format
                                                $badgeClass = match($control->format) {
                                                    'percent'    => 'badge-info',
                                                    'multiplier' => 'badge-warning',
                                                    'score'      => 'badge-success',
                                                    'integer'    => 'badge-secondary',
                                                    default      => 'badge-light',
                                                };

                                                $formatLabel = match($control->format) {
                                                    'percent'    => '% (enter whole number)',
                                                    'multiplier' => '× (enter whole number × 100)',
                                                    'score'      => 'score (0–100)',
                                                    'integer'    => 'integer',
                                                    default      => $control->format,
                                                };
                                            @endphp
                                            <tr>
                                                <td>
                                                    <code class="font-weight-bold text-primary">{{ $control->key }}</code>
                                                </td>
                                                <td class="font-weight-bold">{{ $control->label }}</td>
                                                <td class="text-muted small">{{ $control->description }}</td>
                                                <td>
                                                    <span class="badge {{ $badgeClass }}">{{ $control->format }}</span>
                                                </td>
                                                <td class="text-center font-weight-bold">
                                                    {{ $control->display_value }}
                                                </td>
                                                <td>
                                                    <input
                                                        type="number"
                                                        name="controls[{{ $control->key }}]"
                                                        value="{{ old('controls.' . $control->key, $editValue) }}"
                                                        class="form-control form-control-sm @error('controls.' . $control->key) is-invalid @enderror"
                                                        step="{{ in_array($control->format, ['score','integer']) ? '1' : '0.01' }}"
                                                        min="0"
                                                        required
                                                    >
                                                    @error('controls.' . $control->key)
                                                        <div class="invalid-feedback small">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                            </tr>
                                        @endforeach

                                        {{-- AIC: computed read-only row (MIC × 12) --}}
                                        @php
                                            $mic = $controls->firstWhere('key', 'MIC');
                                            $aicValue = $mic ? round($mic->value * 12 * 100, 4) : 0;
                                        @endphp
                                        <tr class="table-secondary">
                                            <td>
                                                <code class="font-weight-bold text-secondary">AIC</code>
                                            </td>
                                            <td class="font-weight-bold text-muted">Maximum Annual Interest Cap</td>
                                            <td class="text-muted small">
                                                Derived from MIC × 12. Read-only — update MIC to change this.
                                            </td>
                                            <td>
                                                <span class="badge badge-info">percent</span>
                                            </td>
                                            <td class="text-center font-weight-bold text-muted">
                                                {{ $aicValue }}%
                                            </td>
                                            <td class="text-center text-muted small fst-italic">
                                                <em>auto-calculated</em>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4 d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="mdi mdi-clock-outline mr-1"></i>
                                    Last updated: {{ $controls->max('updated_at')?->format('d M Y H:i') ?? 'N/A' }}
                                </small>
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="mdi mdi-content-save mr-1"></i> Save Policy Controls
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var table = $('#policyTable').DataTable({
        orderCellsTop: true,
        fixedHeader: true,
        pageLength: 25,
        order: [],
        columnDefs: [
            { orderable: false, targets: 5 }
        ]
    });

    // Bind filter inputs in the second header row (index 1)
    $('#policyTable thead tr:eq(1) th').each(function (i) {
        var input = $(this).find('input, select');
        if (input.length) {
            input.on('keyup change', function () {
                if (table.column(i).search() !== this.value) {
                    table.column(i).search(this.value).draw();
                }
            });
            // Prevent sorting when clicking filter row
            input.on('click', function (e) { e.stopPropagation(); });
        }
    });
});
</script>
@endpush
