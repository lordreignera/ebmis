@extends('layouts.admin')

@section('title', 'Bulk SMS Records')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="mdi mdi-message-text"></i> Bulk SMS Records
                    </h3>
                    <a href="{{ route('admin.bulk-sms.create') }}" class="btn btn-primary">
                        <i class="mdi mdi-message-plus"></i> New Campaign
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-check-circle me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="mdi mdi-alert-circle me-2"></i>{{ $errors->first() }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['total_campaigns'] ?? 0 }}</h4>
                                    <small>Total Campaigns</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['total_sent'] ?? 0 }}</h4>
                                    <small>Recipients</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['successful_sent'] ?? 0 }}</h4>
                                    <small>Successful</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['failed_sent'] ?? 0 }}</h4>
                                    <small>Failed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['pending_campaigns'] ?? 0 }}</h4>
                                    <small>Pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-6 mb-3">
                            <div class="card bg-secondary text-white">
                                <div class="card-body">
                                    <h4 class="mb-1">{{ $stats['today_campaigns'] ?? 0 }}</h4>
                                    <small>Today</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('admin.bulk-sms.index') }}" class="row g-3 mb-4">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search title or message..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                @foreach(['pending', 'scheduled', 'sending', 'completed', 'failed'] as $status)
                                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-1 d-flex gap-2">
                            <button type="submit" class="btn btn-primary" title="Filter">
                                <i class="mdi mdi-magnify"></i>
                            </button>
                            <a href="{{ route('admin.bulk-sms.index') }}" class="btn btn-outline-secondary" title="Reset">
                                <i class="mdi mdi-refresh"></i>
                            </a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Recipients</th>
                                    <th>Successful</th>
                                    <th>Failed</th>
                                    <th>Status</th>
                                    <th>Scheduled</th>
                                    <th>Sent By</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bulkSms as $sms)
                                    <tr>
                                        <td>
                                            <strong>{{ $sms->title ?? 'Untitled Campaign' }}</strong>
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit($sms->message, 70) }}</div>
                                        </td>
                                        <td>{{ $sms->recipients_count ?? 0 }}</td>
                                        <td>{{ $sms->successful_count ?? 0 }}</td>
                                        <td>{{ $sms->failed_count ?? 0 }}</td>
                                        <td>
                                            <span class="badge bg-{{ $sms->status === 'completed' ? 'success' : ($sms->status === 'failed' ? 'danger' : ($sms->status === 'scheduled' ? 'info' : 'warning')) }}">
                                                {{ ucfirst($sms->status ?? 'pending') }}
                                            </span>
                                        </td>
                                        <td>{{ optional($sms->scheduled_at)->format('M d, Y H:i') ?? '-' }}</td>
                                        <td>{{ $sms->sentBy->name ?? 'System' }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.bulk-sms.show', $sms) }}" class="btn btn-sm btn-outline-info" title="View">
                                                <i class="mdi mdi-eye"></i>
                                            </a>
                                            @if($sms->status !== 'sending')
                                                <form action="{{ route('admin.bulk-sms.destroy', $sms) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this SMS campaign?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="mdi mdi-delete"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="mdi mdi-message-text-outline mdi-48px text-muted"></i>
                                            <h5 class="mt-3">No SMS campaigns found</h5>
                                            <a href="{{ route('admin.bulk-sms.create') }}" class="btn btn-primary mt-2">
                                                <i class="mdi mdi-message-plus"></i> New Campaign
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $bulkSms->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
