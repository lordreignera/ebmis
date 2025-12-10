<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="mdi mdi-file-document"></i> Document Gallery</h5>
    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
        <i class="mdi mdi-upload"></i> Upload Document
    </button>
</div>

@if($member->documents->count() > 0)
    <div class="row">
        @foreach($member->documents as $document)
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="card-title">
                                    <i class="mdi mdi-file text-primary"></i>
                                    {{ $document->document_name }}
                                </h6>
                                <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</span>
                            </div>
                        </div>
                        @if($document->description)
                            <p class="card-text mt-2"><small>{{ $document->description }}</small></p>
                        @endif
                        <p class="card-text">
                            <small class="text-muted">
                                Size: {{ $document->getFileSizeFormatted() }}<br>
                                Uploaded: {{ $document->created_at->format('M d, Y') }}<br>
                                By: {{ $document->uploadedBy->name ?? 'N/A' }}
                            </small>
                        </p>
                        
                        @if($document->fileExists() && $document->file_url)
                            <div class="btn-group btn-group-sm w-100">
                                <a href="{{ $document->file_url }}" target="_blank" class="btn btn-info btn-sm px-2 py-1" style="font-size: 0.75rem;">
                                    <i class="mdi mdi-eye"></i>
                                </a>
                                <a href="{{ route('admin.members.documents.download', [$member, $document]) }}" class="btn btn-primary btn-sm px-2 py-1" style="font-size: 0.75rem;">
                                    <i class="mdi mdi-download"></i>
                                </a>
                                <form action="{{ route('admin.members.documents.destroy', [$member, $document]) }}" method="POST" class="flex-grow-1" onsubmit="return confirm('Delete this document?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm w-100 px-2 py-1" style="font-size: 0.75rem;">
                                        <i class="mdi mdi-delete"></i>
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="alert alert-danger py-2 px-2 mb-2" style="font-size: 0.75rem;">
                                <i class="mdi mdi-alert"></i> File missing from server
                            </div>
                            <div class="btn-group-vertical w-100 gap-1">
                                <button type="button" class="btn btn-warning btn-sm" 
                                        onclick="showReuploadModal({{ $document->id }}, '{{ $document->document_name }}', '{{ $document->document_type }}')">
                                    <i class="mdi mdi-upload"></i> Re-upload File
                                </button>
                                <form action="{{ route('admin.members.documents.destroy', [$member, $document]) }}" method="POST" onsubmit="return confirm('Delete this document record?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm w-100">
                                        <i class="mdi mdi-delete"></i> Remove Record
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="alert alert-info">
        <i class="mdi mdi-information"></i> No documents uploaded. Upload member documents like ID cards, bank statements, etc.
    </div>
@endif

<!-- Loan Documents Section -->
@php
    $personalLoans = \App\Models\PersonalLoan::where('member_id', $member->id)
        ->whereNotNull(DB::raw('COALESCE(trading_file, bank_file, business_file)'))
        ->get();
@endphp

@if($personalLoans->count() > 0)
    <div class="mt-4">
        <h5 class="mb-3"><i class="mdi mdi-file-document-outline"></i> Loan Documents</h5>
        <div class="row">
            @foreach($personalLoans as $loan)
                @php
                    $loanDocuments = [];
                    if($loan->trading_file) {
                        $loanDocuments[] = [
                            'name' => 'Trading License',
                            'file' => $loan->trading_file,
                            'type' => 'trading',
                            'icon' => 'mdi-store',
                            'color' => 'primary'
                        ];
                    }
                    if($loan->bank_file) {
                        $loanDocuments[] = [
                            'name' => 'Bank Statement',
                            'file' => $loan->bank_file,
                            'type' => 'bank',
                            'icon' => 'mdi-bank',
                            'color' => 'warning'
                        ];
                    }
                    if($loan->business_file) {
                        $loanDocuments[] = [
                            'name' => 'Business Premise',
                            'file' => $loan->business_file,
                            'type' => 'business',
                            'icon' => 'mdi-image',
                            'color' => 'success'
                        ];
                    }
                    if($loan->business_photos) {
                        $loanDocuments[] = [
                            'name' => 'Business Photos',
                            'file' => $loan->business_photos,
                            'type' => 'photos',
                            'icon' => 'mdi-camera-image',
                            'color' => 'info'
                        ];
                    }
                @endphp
                
                @foreach($loanDocuments as $doc)
                    @php
                        $fileExists = \App\Services\FileStorageService::fileExists($doc['file']);
                        $fileUrl = $fileExists ? \App\Services\FileStorageService::getFileUrl($doc['file']) : null;
                    @endphp
                    <div class="col-md-4 mb-3">
                        <div class="card border-{{ $doc['color'] }}">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="mdi {{ $doc['icon'] }} text-{{ $doc['color'] }}"></i>
                                    {{ $doc['name'] }}
                                </h6>
                                <span class="badge bg-{{ $doc['color'] }} mb-2">Loan: {{ $loan->code }}</span>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Loan Date: {{ \Carbon\Carbon::parse($loan->datecreated)->format('M d, Y') }}<br>
                                        Status: <span class="badge bg-{{ $loan->status == 1 ? 'success' : 'warning' }}">
                                            {{ $loan->status == 1 ? 'Active' : 'Pending' }}
                                        </span>
                                    </small>
                                </p>
                                @if($fileExists && $fileUrl)
                                    <div class="btn-group btn-group-sm w-100">
                                        <a href="{{ $fileUrl }}" target="_blank" class="btn btn-{{ $doc['color'] }} btn-sm px-2 py-1" style="font-size: 0.75rem;">
                                            <i class="mdi mdi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.loans.show', ['id' => $loan->id, 'type' => 'personal']) }}" class="btn btn-outline-{{ $doc['color'] }} btn-sm px-2 py-1" style="font-size: 0.75rem;">
                                            <i class="mdi mdi-open-in-new"></i>
                                        </a>
                                    </div>
                                @else
                                    <span class="badge bg-danger">File Missing</span>
                                    <a href="{{ route('admin.loans.show', ['id' => $loan->id, 'type' => 'personal']) }}" class="btn btn-sm btn-outline-secondary mt-2 w-100">
                                        <i class="mdi mdi-upload"></i> Re-upload in Loan
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
@endif

<!-- All modals moved to main show.blade.php outside tab content to fix blinking issue -->
