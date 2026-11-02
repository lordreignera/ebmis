@extends('layouts.admin')

@section('content')
@include('admin.partials.page-header', [
    'title' => 'Investor Portal Approvals',
    'subtitle' => 'Review investor identity information before granting access to the separate investor portal.',
    'icon' => 'mdi mdi-account-check-outline',
    'backFallback' => route('admin.access-control.index'),
    'backLabel' => 'Back to Access Control',
])

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="d-flex flex-wrap gap-2 mb-4">
    @foreach(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
        <a href="{{ route('admin.investments.investor-approvals.index', ['status' => $value]) }}"
           class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $label }} ({{ number_format($counts[$value] ?? 0) }})
        </a>
    @endforeach
</div>

<div class="access-control-table-shell investor-approval-directory">
    <div class="access-control-table-scroll">
        <table class="table access-control-table mb-0">
            <thead>
                <tr>
                    <th>Investor</th>
                    <th>Contact</th>
                    <th>Identification</th>
                    <th>Location</th>
                    <th>Documents</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($investors as $investor)
                    @php
                        $statusClass = $investor->portal_status === 'approved'
                            ? 'green'
                            : ($investor->portal_status === 'rejected' ? 'red' : 'orange');
                        $investorDetails = [
                            'name' => $investor->full_name,
                            'reference' => '#'.$investor->id,
                            'title' => $investor->title_name ?: 'Not set',
                            'gender' => $investor->gender ?: 'Not set',
                            'dob' => $investor->dob ?: 'Not set',
                            'email' => $investor->email ?: 'Not set',
                            'phone' => $investor->phone ?: 'Not set',
                            'address' => $investor->address ?: 'Not set',
                            'city' => $investor->city ?: 'Not set',
                            'country' => $investor->countryDetails->name ?? 'Not set',
                            'zip' => $investor->zip ?: 'Not set',
                            'id_type' => $investor->IDtype ?: 'Not set',
                            'id_number' => $investor->IDnumber ?: 'Not set',
                            'status' => $investor->portal_status_label,
                            'rejection_reason' => $investor->rejection_reason ?: 'None',
                            'identity_url' => route('admin.investments.investor-approvals.document', [$investor, 'identity']),
                            'photo_url' => route('admin.investments.investor-approvals.document', [$investor, 'photo']),
                        ];
                    @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="investor-avatar"><i class="mdi mdi-account-cash-outline"></i></div>
                                <div>
                                    <div class="table-primary-text">{{ $investor->full_name }}</div>
                                    <div class="table-secondary-text">Registration {{ $investorDetails['reference'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="table-primary-text">{{ $investor->email }}</div>
                            <div class="table-secondary-text">{{ $investor->phone ?: 'No phone provided' }}</div>
                        </td>
                        <td>
                            <div class="table-primary-text">{{ $investor->IDtype }}</div>
                            <div class="table-secondary-text">{{ $investor->IDnumber }}</div>
                        </td>
                        <td>
                            <div class="table-primary-text">{{ $investor->city ?: 'Not set' }}</div>
                            <div class="table-secondary-text">{{ $investor->countryDetails->name ?? 'Not set' }}</div>
                        </td>
                        <td>
                            <div class="document-actions">
                                <a class="access-control-action" href="{{ $investorDetails['identity_url'] }}" title="View identity document">
                                    <i class="mdi mdi-card-account-details-outline"></i><span>ID</span>
                                </a>
                                <a class="access-control-action" href="{{ $investorDetails['photo_url'] }}" title="View profile photo">
                                    <i class="mdi mdi-account-box-outline"></i><span>Photo</span>
                                </a>
                            </div>
                        </td>
                        <td>
                            <span class="access-control-badge {{ $statusClass }}">{{ $investor->portal_status_label }}</span>
                            @if($investor->rejection_reason)
                                <div class="table-secondary-text approval-reason">{{ $investor->rejection_reason }}</div>
                            @endif
                        </td>
                        <td class="action-cell">
                            <div class="access-control-actions">
                                <button type="button" class="access-control-action view" title="View all investor details"
                                    data-bs-toggle="modal" data-bs-target="#investorDetailsModal"
                                    data-investor-details="{{ json_encode($investorDetails, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}">
                                    <i class="mdi mdi-eye-outline"></i><span>View</span>
                                </button>
                                @if($investor->portal_status !== 'approved')
                                    <form method="POST" action="{{ route('admin.investments.investor-approvals.approve', $investor) }}">
                                        @csrf
                                        <button class="access-control-action approve" type="submit" title="Approve investor">
                                            <i class="mdi mdi-check-circle-outline"></i><span>Approve</span>
                                        </button>
                                    </form>
                                @endif
                                @if($investor->portal_status !== 'rejected')
                                    <button class="access-control-action reject" type="button" title="Reject investor"
                                        data-bs-toggle="modal" data-bs-target="#rejectInvestor{{ $investor->id }}">
                                        <i class="mdi mdi-close-circle-outline"></i><span>Reject</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="mdi mdi-account-search-outline" style="font-size: 2rem"></i>
                            <div class="mt-2">No {{ $status }} investor registrations.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($investors->hasPages())
        <div class="access-control-table-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
            <span class="table-secondary-text">
                Showing {{ $investors->firstItem() }} to {{ $investors->lastItem() }} of {{ $investors->total() }} registrations
            </span>
            <div>{{ $investors->links() }}</div>
        </div>
    @endif
</div>

<div class="modal fade investor-approval-modal" id="investorDetailsModal" tabindex="-1" aria-labelledby="investorDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content investor-details-modal">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="investorDetailsModalLabel">Investor details</h5>
                    <div class="table-secondary-text" id="investorDetailsReference"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="investor-details-heading">
                    <div class="investor-avatar large"><i class="mdi mdi-account-cash-outline"></i></div>
                    <div>
                        <h3 id="investorDetailsName">Investor</h3>
                        <span class="access-control-badge slate" id="investorDetailsStatus">Pending review</span>
                    </div>
                </div>
                <div class="investor-details-grid">
                    <div class="detail-group"><span>Title</span><strong id="detailTitle"></strong></div>
                    <div class="detail-group"><span>Gender</span><strong id="detailGender"></strong></div>
                    <div class="detail-group"><span>Date of birth</span><strong id="detailDob"></strong></div>
                    <div class="detail-group"><span>Email</span><strong id="detailEmail"></strong></div>
                    <div class="detail-group"><span>Phone</span><strong id="detailPhone"></strong></div>
                    <div class="detail-group"><span>Address</span><strong id="detailAddress"></strong></div>
                    <div class="detail-group"><span>City / district</span><strong id="detailCity"></strong></div>
                    <div class="detail-group"><span>Country</span><strong id="detailCountry"></strong></div>
                    <div class="detail-group"><span>Postal code</span><strong id="detailZip"></strong></div>
                    <div class="detail-group"><span>Identification type</span><strong id="detailIdType"></strong></div>
                    <div class="detail-group"><span>Identification number</span><strong id="detailIdNumber"></strong></div>
                    <div class="detail-group"><span>Rejection reason</span><strong id="detailRejectionReason"></strong></div>
                </div>
                <div class="details-documents">
                    <span>Private documents</span>
                    <div>
                        <a id="detailIdentityLink" class="btn btn-sm btn-outline-primary" href="#"><i class="mdi mdi-card-account-details-outline"></i> View identity document</a>
                        <a id="detailPhotoLink" class="btn btn-sm btn-outline-secondary" href="#"><i class="mdi mdi-account-box-outline"></i> View profile photo</a>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@foreach($investors as $investor)
    @if($investor->portal_status !== 'rejected')
        <div class="modal fade investor-approval-modal" id="rejectInvestor{{ $investor->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form method="POST" action="{{ route('admin.investments.investor-approvals.reject', $investor) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Reject {{ $investor->full_name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label for="reason-{{ $investor->id }}" class="form-label">Reason for rejection</label>
                            <textarea id="reason-{{ $investor->id }}" class="form-control" name="reason" rows="4" minlength="10" maxlength="1000" required></textarea>
                            <small class="text-muted">This reason remains on the administrative review record.</small>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Confirm rejection</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection

@push('styles')
<style>
    /* The legacy admin theme has a dark modal skin and a z-index above Bootstrap.
       Keep this approval flow above the sidebar and use the shared light UI. */
    .modal.investor-approval-modal { z-index: 10050 !important; }
    body.investor-approval-modal-active .modal-backdrop { z-index: 10040 !important; }
    #investorDetailsModal .modal-dialog { max-width: 920px !important; width: calc(100% - 2rem); margin: 1.75rem auto; }
    .investor-approval-modal .modal-content,
    .investor-details-modal,
    .investor-approval-modal .modal-body,
    .investor-approval-modal .modal-footer {
        background: #ffffff !important;
        color: #0f172a !important;
    }
    .investor-details-modal { border: 1px solid #dbe5ec !important; border-radius: .65rem !important; box-shadow: 0 1rem 3rem rgba(15, 23, 42, .2) !important; }
    .investor-approval-modal .modal-header { background: #ffffff !important; color: #0f172a !important; }
    .investor-approval-modal .modal-footer { border-top: 1px solid #e2e8f0 !important; }
    .investor-approval-modal .btn-close { filter: none !important; opacity: .7; }
    .investor-approval-modal .btn-close:hover { opacity: 1; }
    .investor-approval-directory .access-control-table { min-width: 1180px; }
    .investor-avatar { align-items: center; background: #eff6ff; border: 1px solid #dbeafe; border-radius: 50%; color: #2563eb; display: inline-flex; flex: 0 0 38px; height: 38px; justify-content: center; width: 38px; }
    .investor-avatar.large { flex-basis: 48px; font-size: 1.35rem; height: 48px; width: 48px; }
    .document-actions { display: flex; flex-wrap: wrap; gap: .4rem; }
    .document-actions .access-control-action { padding: .35rem .55rem; }
    .access-control-table .access-control-action.view { border-color: #bfdbfe !important; color: #1d4ed8 !important; }
    .access-control-table .access-control-action.view:hover { background: #eff6ff !important; border-color: #60a5fa !important; }
    .access-control-table .access-control-action.approve { border-color: #bbf7d0 !important; color: #166534 !important; }
    .access-control-table .access-control-action.approve:hover { background: #f0fdf4 !important; border-color: #4ade80 !important; }
    .access-control-table .access-control-action.reject { border-color: #fecaca !important; color: #b91c1c !important; }
    .access-control-table .access-control-action.reject:hover { background: #fef2f2 !important; border-color: #f87171 !important; }
    .approval-reason { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .investor-details-modal .modal-header { align-items: flex-start; }
    .investor-details-modal .table-secondary-text { color: #64748b; font-size: .75rem; }
    .investor-details-modal .access-control-badge { border: 1px solid transparent; border-radius: .25rem; display: inline-flex; font-size: .72rem; font-weight: 700; line-height: 1.2; padding: .25rem .5rem; }
    .investor-details-modal .access-control-badge.green { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    .investor-details-modal .access-control-badge.orange { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
    .investor-details-modal .access-control-badge.red { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
    .investor-details-modal .access-control-badge.slate { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
    .investor-details-heading { align-items: center; border-bottom: 1px solid #e2e8f0; display: flex; gap: .75rem; margin-bottom: 1rem; padding-bottom: 1rem; }
    .investor-details-heading h3 { color: #0f172a; font-size: 1.15rem; margin: 0 0 .35rem; }
    .investor-details-grid { display: grid; gap: .85rem 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .detail-group { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .35rem; padding: .65rem .75rem; }
    .detail-group span, .details-documents > span { color: #64748b; display: block; font-size: .7rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .detail-group strong { color: #0f172a; display: block; font-size: .85rem; margin-top: .2rem; overflow-wrap: anywhere; }
    .details-documents { border-top: 1px solid #e2e8f0; margin-top: 1rem; padding-top: 1rem; }
    .details-documents > div { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .55rem; }
    @media (max-width: 576px) { .investor-details-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('show.bs.modal', function (event) {
        if (event.target.classList.contains('investor-approval-modal')) {
            document.body.classList.add('investor-approval-modal-active');
        }
    });

    document.addEventListener('hidden.bs.modal', function (event) {
        if (!event.target.classList.contains('investor-approval-modal')) {
            return;
        }

        window.setTimeout(function () {
            if (!document.querySelector('.investor-approval-modal.show')) {
                document.body.classList.remove('investor-approval-modal-active');
            }
        }, 0);
    });

    document.querySelectorAll('[data-investor-details]').forEach(function (button) {
        button.addEventListener('click', function () {
            let details;

            try {
                details = JSON.parse(button.dataset.investorDetails);
            } catch (error) {
                console.error('Unable to read investor details.', error);
                return;
            }

            const setText = function (id, value) { document.getElementById(id).textContent = value || 'Not set'; };

            setText('investorDetailsName', details.name);
            setText('investorDetailsReference', 'Registration ' + details.reference);
            const statusBadge = document.getElementById('investorDetailsStatus');
            setText('investorDetailsStatus', details.status);
            statusBadge.className = 'access-control-badge ' + ({
                'Approved': 'green',
                'Rejected': 'red',
                'Pending review': 'orange'
            }[details.status] || 'slate');
            setText('detailTitle', details.title);
            setText('detailGender', details.gender);
            setText('detailDob', details.dob);
            setText('detailEmail', details.email);
            setText('detailPhone', details.phone);
            setText('detailAddress', details.address);
            setText('detailCity', details.city);
            setText('detailCountry', details.country);
            setText('detailZip', details.zip);
            setText('detailIdType', details.id_type);
            setText('detailIdNumber', details.id_number);
            setText('detailRejectionReason', details.rejection_reason);
            document.getElementById('detailIdentityLink').href = details.identity_url;
            document.getElementById('detailPhotoLink').href = details.photo_url;
        });
    });
</script>
@endpush
