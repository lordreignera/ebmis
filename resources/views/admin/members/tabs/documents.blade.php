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
                        <div class="btn-group w-100">
                            <a href="{{ $document->file_url }}" target="_blank" class="btn btn-sm btn-info">
                                <i class="mdi mdi-eye"></i> View
                            </a>
                            <a href="{{ route('admin.members.documents.download', [$member, $document]) }}" class="btn btn-sm btn-primary">
                                <i class="mdi mdi-download"></i> Download
                            </a>
                            <form action="{{ route('admin.members.documents.destroy', [$member, $document]) }}" method="POST" class="flex-grow-1" onsubmit="return confirm('Delete this document?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger w-100">
                                    <i class="mdi mdi-delete"></i> Delete
                                </button>
                            </form>
                        </div>
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

<!-- All modals moved to main show.blade.php outside tab content to fix blinking issue -->
