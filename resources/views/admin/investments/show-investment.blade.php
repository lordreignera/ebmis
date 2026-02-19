@extends('layouts.admin')

@section('title', 'Investment Details - ' . $investment->name)

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="font-weight-bold mb-0">Investment Details</h4>
                <p class="text-muted mb-0">{{ $investment->name }} - {{ $investment->investor->full_name }}</p>
            </div>
            <div>
                <a href="{{ route('admin.investments.edit-investment', $investment->id) }}" class="btn btn-success btn-sm">
                    <i class="mdi mdi-pencil"></i> Edit
                </a>
                <a href="{{ route('admin.investments.index') }}" class="btn btn-secondary btn-sm">
                    <i class="mdi mdi-arrow-left"></i> Back to Investments
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Investment Overview -->
    <div class="col-lg-8">
        <div class="card card-bordered mb-4">
            <div class="card-header">
                <h6 class="mb-0">Investment Overview</h6>
                @php
                    $startDate = null;
                    $endDate = null;
                    try {
                        $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->start));
                    } catch (\Exception $e) {
                        try {
                            $startDate = \Carbon\Carbon::parse($investment->start);
                        } catch (\Exception $e2) {
                            $startDate = null;
                        }
                    }
                    try {
                        $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->end));
                    } catch (\Exception $e) {
                        try {
                            $endDate = \Carbon\Carbon::parse($investment->end);
                        } catch (\Exception $e2) {
                            $endDate = null;
                        }
                    }
                    $now = \Carbon\Carbon::now();
                    
                    if ($now->lt($startDate)) {
                        $statusClass = 'badge-secondary';
                        $statusText = 'Pending';
                        $statusIcon = 'mdi-clock';
                    } elseif ($now->between($startDate, $endDate)) {
                        $statusClass = 'badge-success';
                        $statusText = 'Active';
                        $statusIcon = 'mdi-play';
                    } else {
                        $statusClass = 'badge-warning';
                        $statusText = 'Matured';
                        $statusIcon = 'mdi-check';
                    }
                @endphp
                <span class="badge {{ $statusClass }}">
                    <i class="{{ $statusIcon }}"></i> {{ $statusText }}
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-primary">Investment Information</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Investment Name:</span>
                            <span class="font-weight-medium">{{ $investment->name }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Investment Amount:</span>
                            <span class="font-weight-medium text-primary">${{ number_format($investment->amount, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Investment Type:</span>
                            <span class="font-weight-medium">
                                @if($investment->type == 1)
                                    <span class="badge badge-primary">Standard Interest</span>
                                @else
                                    <span class="badge badge-info">Compound Interest</span>
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Investment Period:</span>
                            <span class="font-weight-medium">{{ $investment->period }} year{{ $investment->period > 1 ? 's' : '' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Interest Rate:</span>
                            <span class="font-weight-medium">{{ number_format($investment->percentage, 2) }}%</span>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-success">Financial Summary</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Interest:</span>
                            <span class="font-weight-medium text-success">${{ number_format($investment->interest, 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Return:</span>
                            <span class="font-weight-medium text-primary">${{ number_format(($investment->amount + $investment->interest), 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Conversion Fee:</span>
                            <span class="font-weight-medium">${{ number_format(($investment->amount * 0.005), 2) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Charge:</span>
                            <span class="font-weight-medium">${{ number_format(($investment->amount + ($investment->amount * 0.005)), 2) }}</span>
                        </div>
                        @if($investment->type == 1)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Annual Profit:</span>
                            <span class="font-weight-medium">${{ $investment->period > 0 ? number_format(($investment->interest / $investment->period), 2) : '0.00' }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="text-info">Investment Timeline</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Start Date:</span>
                            <span class="font-weight-medium">
                                @if(!empty($investment->start))
                                    @php
                                        try {
                                            $date = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->start));
                                            echo $date ? $date->format('F d, Y') : $investment->start;
                                        } catch (\Exception $e) {
                                            try {
                                                $date = \Carbon\Carbon::parse($investment->start);
                                                echo $date ? $date->format('F d, Y') : $investment->start;
                                            } catch (\Exception $e2) {
                                                echo $investment->start;
                                            }
                                        }
                                    @endphp
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">End Date:</span>
                            <span class="font-weight-medium">
                                @if(!empty($investment->end))
                                    @php
                                        try {
                                            $date = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->end));
                                            echo $date ? $date->format('F d, Y') : $investment->end;
                                        } catch (\Exception $e) {
                                            try {
                                                $date = \Carbon\Carbon::parse($investment->end);
                                                echo $date ? $date->format('F d, Y') : $investment->end;
                                            } catch (\Exception $e2) {
                                                echo $investment->end;
                                            }
                                        }
                                    @endphp
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Days Remaining:</span>
                            @php
                                $daysRemaining = $endDate ? $now->diffInDays($endDate, false) : 0;
                            @endphp
                            <span class="font-weight-medium">
                                @if(!$endDate)
                                    <span class="text-muted">N/A</span>
                                @elseif($daysRemaining > 0)
                                    {{ $daysRemaining }} days
                                @elseif($daysRemaining == 0)
                                    <span class="text-warning">Due Today</span>
                                @else
                                    <span class="text-danger">{{ abs($daysRemaining) }} days overdue</span>
                                @endif
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Progress:</span>
                            <span class="font-weight-medium">
                                @php
                                    $totalDays = 0;
                                    $daysPassed = 0;
                                    $progress = 0;
                                    if ($startDate && $endDate) {
                                        $totalDays = $startDate->diffInDays($endDate);
                                        $daysPassed = $startDate->diffInDays($now);
                                        $progress = $totalDays > 0 ? min(100, max(0, ($daysPassed / $totalDays) * 100)) : 0;
                                    }
                                @endphp
                                {{ number_format($progress, 1) }}%
                            </span>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        @if($investment->areas)
                        <h6 class="text-warning">Investment Areas</h6>
                        @php $areas = json_decode($investment->areas, true) ?: []; @endphp
                        @foreach($areas as $area)
                        <span class="badge badge-outline-primary mb-1">{{ $area }}</span>
                        @endforeach
                        @endif
                    </div>
                </div>

                @if($investment->details)
                <div class="mb-3">
                    <h6 class="mb-2">Payment Details</h6>
                    <div class="card bg-light border-0">
                        <div class="card-body p-3">
                            <p class="mb-0">{{ $investment->details }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Progress Bar -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Investment Progress</span>
                        <span class="font-weight-medium">{{ number_format($progress, 1) }}%</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar 
                            @if($progress < 25) bg-danger
                            @elseif($progress < 50) bg-warning
                            @elseif($progress < 75) bg-info
                            @else bg-success
                            @endif"
                            role="progressbar" 
                            style="width: {{ $progress }}%"
                            aria-valuenow="{{ $progress }}" 
                            aria-valuemin="0" 
                            aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Investment Timeline -->
        <div class="card card-bordered">
            <div class="card-header">
                <h6 class="mb-0">Investment Timeline</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item {{ $now->gte($startDate) ? 'completed' : 'pending' }}">
                        <div class="timeline-marker">
                            <i class="mdi {{ $now->gte($startDate) ? 'mdi-check' : 'mdi-clock' }}"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Investment Started</h6>
                            <p class="timeline-text">
                                @if(!empty($investment->start))
                                    @php
                                        try {
                                            $date = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->start));
                                            echo $date ? $date->format('F d, Y') : $investment->start;
                                        } catch (\Exception $e) {
                                            try {
                                                $date = \Carbon\Carbon::parse($investment->start);
                                                echo $date ? $date->format('F d, Y') : $investment->start;
                                            } catch (\Exception $e2) {
                                                echo $investment->start;
                                            }
                                        }
                                    @endphp
                                @else
                                    Start Date
                                @endif
                            </p>
                            <small class="text-muted">
                                @if(!empty($investment->start))
                                    @php
                                        try {
                                            $date = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->start));
                                            echo $date ? $date->diffForHumans() : '';
                                        } catch (\Exception $e) {
                                            try {
                                                $date = \Carbon\Carbon::parse($investment->start);
                                                echo $date ? $date->diffForHumans() : '';
                                            } catch (\Exception $e2) {
                                                echo '';
                                            }
                                        }
                                    @endphp
                                @endif
                            </small>
                        </div>
                    </div>

                    @php
                        $milestones = [];
                        $start = null;
                        $end = null;
                        try {
                            $start = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->start));
                        } catch (\Exception $e) {
                            try {
                                $start = \Carbon\Carbon::parse($investment->start);
                            } catch (\Exception $e2) {
                                $start = null;
                            }
                        }
                        try {
                            $end = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->end));
                        } catch (\Exception $e) {
                            try {
                                $end = \Carbon\Carbon::parse($investment->end);
                            } catch (\Exception $e2) {
                                $end = null;
                            }
                        }
                        $period = $investment->period;
                        
                        if ($start && $end && $period > 0) {
                            for ($i = 1; $i < $period; $i++) {
                                $milestones[] = $start->copy()->addYears($i);
                            }
                        }
                    @endphp

                    @foreach($milestones as $milestone)
                    <div class="timeline-item {{ $now->gte($milestone) ? 'completed' : 'pending' }}">
                        <div class="timeline-marker">
                            <i class="mdi {{ $now->gte($milestone) ? 'mdi-check' : 'mdi-clock' }}"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Year {{ $loop->iteration }} Milestone</h6>
                            <p class="timeline-text">{{ $milestone->format('F d, Y') }}</p>
                            <small class="text-muted">{{ $milestone->diffForHumans() }}</small>
                        </div>
                    </div>
                    @endforeach

                    <div class="timeline-item {{ $now->gte($endDate) ? 'completed' : 'pending' }}">
                        <div class="timeline-marker">
                            <i class="mdi {{ $now->gte($endDate) ? 'mdi-check' : 'mdi-clock' }}"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="timeline-title">Investment Maturity</h6>
                            <p class="timeline-text">
                                @if(!empty($investment->end))
                                    @php
                                        try {
                                            $date = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->end));
                                            echo $date ? $date->format('F d, Y') : $investment->end;
                                        } catch (\Exception $e) {
                                            try {
                                                $date = \Carbon\Carbon::parse($investment->end);
                                                echo $date ? $date->format('F d, Y') : $investment->end;
                                            } catch (\Exception $e2) {
                                                echo $investment->end;
                                            }
                                        }
                                    @endphp
                                @else
                                    End Date
                                @endif
                            </p>
                            <small class="text-muted">
                                @if(!empty($investment->end))
                                    @php
                                        try {
                                            $date = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->end));
                                            echo $date ? $date->diffForHumans() : '';
                                        } catch (\Exception $e) {
                                            try {
                                                $date = \Carbon\Carbon::parse($investment->end);
                                                echo $date ? $date->diffForHumans() : '';
                                            } catch (\Exception $e2) {
                                                echo '';
                                            }
                                        }
                                    @endphp
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Investor & Quick Actions -->
    <div class="col-lg-4">
        <!-- Investor Information -->
        <div class="card card-bordered mb-4">
            <div class="card-header">
                <h6 class="mb-0">Investor Information</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-sm mr-3">
                        <div class="avatar-title bg-primary text-white rounded-circle">
                            {{ strtoupper(substr($investment->investor->first_name, 0, 1) . substr($investment->investor->last_name, 0, 1)) }}
                        </div>
                    </div>
                    <div>
                        <h6 class="mb-0">{{ $investment->investor->full_name }}</h6>
                        <p class="text-muted mb-0">{{ ucfirst($investment->investor->type) }} Investor</p>
                    </div>
                </div>
                
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Email:</span>
                        <span>{{ $investment->investor->email }}</span>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Phone:</span>
                        <span>{{ $investment->investor->phone }}</span>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Country:</span>
                        <span>{{ $investment->investor->country->name ?? 'N/A' }}</span>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="{{ route('admin.investments.show-investor', $investment->investor->id) }}" 
                       class="btn btn-primary btn-sm w-100">
                        <i class="mdi mdi-account"></i> View Full Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card card-bordered mb-4">
            <div class="card-header">
                <h6 class="mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.investments.edit-investment', $investment->id) }}" 
                       class="btn btn-success btn-sm">
                        <i class="mdi mdi-pencil"></i> Edit Investment
                    </a>
                    
                    <a href="{{ route('admin.investments.create-investment', $investment->userid) }}" 
                       class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus"></i> New Investment
                    </a>
                    
                    <button type="button" class="btn btn-info btn-sm" onclick="printInvestment()">
                        <i class="mdi mdi-printer"></i> Print Details
                    </button>
                    
                    <button type="button" class="btn btn-warning btn-sm" onclick="exportInvestment()">
                        <i class="mdi mdi-download"></i> Export Data
                    </button>
                    
                    <hr>
                    
                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteModal">
                        <i class="mdi mdi-delete"></i> Delete Investment
                    </button>
                </div>
            </div>
        </div>

        <!-- Investment Statistics -->
        <div class="card card-bordered">
            <div class="card-header">
                <h6 class="mb-0">Investment Statistics</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">ROI:</span>
                        <span class="font-weight-bold text-success">
                            @if($investment->amount > 0)
                                {{ number_format(($investment->interest / $investment->amount) * 100, 2) }}%
                            @else
                                0.00%
                            @endif
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Daily Interest:</span>
                        <span class="font-weight-medium">
                            @if($investment->period > 0)
                                ${{ number_format($investment->interest / ($investment->period * 365), 2) }}
                            @else
                                $0.00
                            @endif
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Monthly Interest:</span>
                        <span class="font-weight-medium">
                            @if($investment->period > 0)
                                ${{ number_format($investment->interest / ($investment->period * 12), 2) }}
                            @else
                                $0.00
                            @endif
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Created:</span>
                        <span>
                            @php
                                try {
                                    $createdDate = \Carbon\Carbon::createFromFormat('m/d/Y', trim($investment->datecreated));
                                    echo $createdDate ? $createdDate->format('M d, Y') : ($investment->datecreated ?? 'N/A');
                                } catch (\Exception $e) {
                                    try {
                                        $createdDate = \Carbon\Carbon::parse($investment->datecreated);
                                        echo $createdDate ? $createdDate->format('M d, Y') : ($investment->datecreated ?? 'N/A');
                                    } catch (\Exception $e2) {
                                        echo $investment->datecreated ?? 'N/A';
                                    }
                                }
                            @endphp
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Investment</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the investment <strong>"{{ $investment->name }}"</strong>?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone and will permanently remove all investment data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.investments.destroy', $investment->id) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete Investment</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function printInvestment() {
    window.print();
}

function exportInvestment() {
    // Create export data
    const data = {
        investment_name: '{{ $investment->name }}',
        investor_name: '{{ $investment->investor->full_name }}',
        amount: {{ $investment->amount }},
        type: '{{ $investment->type == 1 ? "Standard Interest" : "Compound Interest" }}',
        period: '{{ $investment->period }} years',
        rate: '{{ $investment->percentage }}%',
        interest: {{ $investment->interest }},
        total_return: {{ $investment->amount + $investment->interest }},
        start_date: '{{ $investment->start }}',
        end_date: '{{ $investment->end }}'
    };
    
    // Convert to CSV
    let csv = 'Field,Value\n';
    for (const [key, value] of Object.entries(data)) {
        csv += `"${key.replace(/_/g, ' ').toUpperCase()}","${value}"\n`;
    }
    
    // Download
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'investment_{{ $investment->id }}_{{ $investment->name }}.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>
@endpush

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.timeline-item.completed .timeline-marker {
    background: #28a745;
    color: white;
}

.timeline-item.pending .timeline-marker {
    background: #6c757d;
    color: white;
}

.timeline-content {
    padding-left: 20px;
}

.timeline-title {
    margin-bottom: 5px;
    font-size: 14px;
}

.timeline-text {
    margin-bottom: 5px;
    font-size: 13px;
}

@media print {
    .btn, .modal, .timeline-marker {
        display: none !important;
    }
}
</style>
@endpush
@endsection