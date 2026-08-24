@extends('layouts.admin')

@section('title', 'Send Bulk SMS')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="mdi mdi-message-plus"></i> Send Bulk SMS
                    </h3>
                </div>

                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.bulk-sms.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="title" class="form-label">Campaign Title</label>
                                <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="recipient_type" class="form-label">Recipients</label>
                                <select name="recipient_type" id="recipient_type" class="form-select" required>
                                    <option value="all" {{ old('recipient_type') === 'all' ? 'selected' : '' }}>All approved members</option>
                                    <option value="group" {{ old('recipient_type') === 'group' ? 'selected' : '' }}>Saved member group</option>
                                    <option value="individual" {{ old('recipient_type') === 'individual' ? 'selected' : '' }}>Select individuals</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 d-none" id="recipientGroupWrap">
                            <label for="recipient_group" class="form-label">Member Group</label>
                            <select name="recipient_group" id="recipient_group" class="form-select">
                                <option value="">Choose a group</option>
                                @foreach($memberGroups as $value => $label)
                                    <option value="{{ $value }}" {{ old('recipient_group') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="individualRecipientsWrap">
                            <label for="recipients" class="form-label">Members</label>
                            <select name="recipients[]" id="recipients" class="form-select" multiple size="10">
                                @foreach($members as $member)
                                    <option value="{{ $member->id }}" {{ in_array($member->id, old('recipients', [])) ? 'selected' : '' }}>
                                        {{ $member->fname }} {{ $member->lname }} - {{ $member->contact }}{{ $member->code ? ' (' . $member->code . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea name="message" id="message" class="form-control" rows="5" maxlength="1000" required>{{ old('message') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="schedule_type" class="form-label">Schedule</label>
                                <select name="schedule_type" id="schedule_type" class="form-select" required>
                                    <option value="now" {{ old('schedule_type') === 'now' ? 'selected' : '' }}>Send now</option>
                                    <option value="later" {{ old('schedule_type') === 'later' ? 'selected' : '' }}>Schedule for later</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 d-none" id="scheduledAtWrap">
                                <label for="scheduled_at" class="form-label">Scheduled Time</label>
                                <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.bulk-sms.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="mdi mdi-send"></i> Save Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const recipientType = document.getElementById('recipient_type');
    const recipientGroupWrap = document.getElementById('recipientGroupWrap');
    const individualRecipientsWrap = document.getElementById('individualRecipientsWrap');
    const scheduleType = document.getElementById('schedule_type');
    const scheduledAtWrap = document.getElementById('scheduledAtWrap');

    function syncRecipientFields() {
        recipientGroupWrap.classList.toggle('d-none', recipientType.value !== 'group');
        individualRecipientsWrap.classList.toggle('d-none', recipientType.value !== 'individual');
    }

    function syncScheduleFields() {
        scheduledAtWrap.classList.toggle('d-none', scheduleType.value !== 'later');
    }

    recipientType.addEventListener('change', syncRecipientFields);
    scheduleType.addEventListener('change', syncScheduleFields);
    syncRecipientFields();
    syncScheduleFields();
});
</script>
@endpush
