<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5><i class="mdi mdi-folder-multiple"></i> Attachment Library</h5>
        <p class="text-muted mb-0"><small>Documents migrated from old BIMS system</small></p>
    </div>
</div>

@if($member->attachmentLibrary->count() > 0)
    <div class="row">
        @foreach($member->attachmentLibrary as $document)
            <div class="col-md-4 mb-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="card-title">
                                    <i class="mdi mdi-file-document text-primary"></i>
                                    {{ $document->document_name }}
                                </h6>
                                <span class="badge bg-primary">Legacy Document</span>
                            </div>
                        </div>
                        @if($document->description)
                            <p class="card-text mt-2"><small>{{ $document->description }}</small></p>
                        @endif
                        <p class="card-text">
                            <small class="text-muted">
                                Size: {{ $document->getFileSizeFormatted() }}<br>
                                Date: {{ $document->created_at ? $document->created_at->format('M d, Y') : 'N/A' }}<br>
                                Added by: {{ $document->uploadedBy->name ?? 'Legacy System' }}
                            </small>
                        </p>
                        <div class="btn-group w-100">
                            <a href="{{ $document->file_url }}" target="_blank" class="btn btn-sm btn-info">
                                <i class="mdi mdi-eye"></i> View
                            </a>
                            <a href="{{ route('admin.members.documents.download', [$member, $document]) }}" class="btn btn-sm btn-primary">
                                <i class="mdi mdi-download"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    
    <div class="alert alert-info mt-3">
        <i class="mdi mdi-information"></i> 
        <strong>Note:</strong> These are historical documents from the previous system. 
        To upload new documents, please use the <strong>Documents</strong> tab.
    </div>
@else
    <div class="alert alert-info">
        <i class="mdi mdi-information"></i> No legacy documents found for this member.
    </div>
@endif
