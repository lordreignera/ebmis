@extends('layouts.admin')

@section('title', 'Balance Sheet')

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Balance Sheet</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                        <li class="breadcrumb-item active">Balance Sheet</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Date Filter -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.accounting.balance-sheet') }}" class="row align-items-end">
                    <div class="col-md-4">
                        <label><i class="mdi mdi-calendar me-1"></i>As of Date</label>
                        <input type="date" class="form-control" name="as_of_date" value="{{ $asOfDate }}">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary"><i class="mdi mdi-refresh me-1"></i>Refresh</button>
                        <button type="button" class="btn btn-success" onclick="window.print()"><i class="mdi mdi-printer me-1"></i>Print</button>
                        <a href="{{ route('admin.accounting.balance-sheet.download', ['as_of_date' => $asOfDate]) }}" class="btn btn-info"><i class="mdi mdi-download me-1"></i>Download PDF</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Balance Sheet Report -->
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <h3 class="mb-0">EBMIS Balance Sheet</h3>
                    <p class="text-muted">As of {{ \Carbon\Carbon::parse($asOfDate)->format('F d, Y') }}</p>
                </div>

                <div class="row">
                    <!-- ASSETS COLUMN -->
                    <div class="col-md-6">
                        <h5 class="bg-primary text-white p-2 mb-0">
                            <i class="mdi mdi-cash-multiple me-2"></i>ASSETS
                        </h5>
                        <table class="table table-sm table-bordered mb-4">
                            <tbody>
                                @foreach($assets as $asset)
                                @if($asset->balance != 0)
                                <tr class="{{ $asset->parent_id ? '' : 'table-light fw-bold' }}">
                                    <td width="70%">
                                        @if($asset->parent_id)
                                        <span class="ms-3">└─</span>
                                        @endif
                                        {{ $asset->name }}
                                        <br><small class="text-muted">{{ $asset->code }} {{ $asset->sub_code ? '- ' . $asset->sub_code : '' }}</small>
                                    </td>
                                    <td width="30%" class="text-end">
                                        {{ number_format($asset->balance, 2) }}
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th>Total Assets</th>
                                    <th class="text-end">UGX {{ number_format($totalAssets, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- LIABILITIES + EQUITY COLUMN -->
                    <div class="col-md-6">
                        <h5 class="bg-danger text-white p-2 mb-0">
                            <i class="mdi mdi-credit-card me-2"></i>LIABILITIES
                        </h5>
                        <table class="table table-sm table-bordered mb-4">
                            <tbody>
                                @foreach($liabilities as $liability)
                                @if($liability->balance != 0)
                                <tr class="{{ $liability->parent_id ? '' : 'table-light fw-bold' }}">
                                    <td width="70%">
                                        @if($liability->parent_id)
                                        <span class="ms-3">└─</span>
                                        @endif
                                        {{ $liability->name }}
                                        <br><small class="text-muted">{{ $liability->code }} {{ $liability->sub_code ? '- ' . $liability->sub_code : '' }}</small>
                                    </td>
                                    <td width="30%" class="text-end">
                                        {{ number_format($liability->balance, 2) }}
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th>Total Liabilities</th>
                                    <th class="text-end">UGX {{ number_format($totalLiabilities, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>

                        <h5 class="bg-success text-white p-2 mb-0">
                            <i class="mdi mdi-account-cash me-2"></i>EQUITY
                        </h5>
                        <table class="table table-sm table-bordered mb-4">
                            <tbody>
                                @foreach($equity as $equityAccount)
                                @if($equityAccount->balance != 0)
                                <tr class="{{ $equityAccount->parent_id ? '' : 'table-light fw-bold' }}">
                                    <td width="70%">
                                        @if($equityAccount->parent_id)
                                        <span class="ms-3">└─</span>
                                        @endif
                                        {{ $equityAccount->name }}
                                        <br><small class="text-muted">{{ $equityAccount->code }} {{ $equityAccount->sub_code ? '- ' . $equityAccount->sub_code : '' }}</small>
                                    </td>
                                    <td width="30%" class="text-end">
                                        {{ number_format($equityAccount->balance, 2) }}
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr>
                                    <th>Total Equity</th>
                                    <th class="text-end">UGX {{ number_format($totalEquity, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>

                        <table class="table table-bordered">
                            <tfoot class="table-dark">
                                <tr>
                                    <th>Total Liabilities + Equity</th>
                                    <th class="text-end">UGX {{ number_format($totalLiabilities + $totalEquity, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Balance Check -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert {{ abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01 ? 'alert-success' : 'alert-danger' }}">
                            <i class="mdi {{ abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01 ? 'mdi-check-circle' : 'mdi-alert' }} me-2"></i>
                            <strong>Accounting Equation Check:</strong> 
                            Assets ({{ number_format($totalAssets, 2) }}) 
                            {{ abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01 ? '=' : '≠' }} 
                            Liabilities + Equity ({{ number_format($totalLiabilities + $totalEquity, 2) }})
                            @if(abs($totalAssets - ($totalLiabilities + $totalEquity)) >= 0.01)
                            <br>Difference: UGX {{ number_format(abs($totalAssets - ($totalLiabilities + $totalEquity)), 2) }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="alert alert-info">
                        <i class="mdi mdi-information me-2"></i><strong>Balance Sheet:</strong> Shows the financial position of the organization at a specific point in time. The fundamental accounting equation (Assets = Liabilities + Equity) must always be balanced.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
