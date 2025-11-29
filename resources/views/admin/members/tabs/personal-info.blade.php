<div class="row">
    <div class="col-md-6">
        <h5><i class="mdi mdi-card-account-details text-primary"></i> Personal Information</h5>
        <table class="table table-borderless">
            <tr>
                <td><strong>NIN:</strong></td>
                <td>{{ $member->nin }}</td>
            </tr>
            <tr>
                <td><strong>Gender:</strong></td>
                <td>{{ $member->gender ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <td><strong>Date of Birth:</strong></td>
                <td>{{ $member->dob ? $member->dob->format('M d, Y') : 'Not specified' }}</td>
            </tr>
            <tr>
                <td><strong>Member Type:</strong></td>
                <td>{{ $member->memberType->name ?? 'Not specified' }}</td>
            </tr>
        </table>

        <h5 class="mt-4"><i class="mdi mdi-phone text-success"></i> Contact Information</h5>
        <table class="table table-borderless">
            <tr>
                <td><strong>Mobile:</strong></td>
                <td>{{ $member->contact }}</td>
            </tr>
            <tr>
                <td><strong>Alternative Contact:</strong></td>
                <td>{{ $member->alt_contact ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <td><strong>Fixed Line:</strong></td>
                <td>{{ $member->fixed_line ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td>{{ $member->email ?? 'Not specified' }}</td>
            </tr>
        </table>
    </div>

    <div class="col-md-6">
        <h5><i class="mdi mdi-map-marker text-danger"></i> Address Information</h5>
        <table class="table table-borderless">
            <tr>
                <td><strong>Plot No:</strong></td>
                <td>{{ $member->plot_no ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <td><strong>Village:</strong></td>
                <td>{{ $member->village ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <td><strong>Parish:</strong></td>
                <td>{{ $member->parish ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <td><strong>Sub-County:</strong></td>
                <td>{{ $member->subcounty ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <td><strong>County:</strong></td>
                <td>{{ $member->county ?? 'Not specified' }}</td>
            </tr>
            <tr>
                <td><strong>Country:</strong></td>
                <td>{{ $member->country->name ?? 'Not specified' }}</td>
            </tr>
        </table>

        <h5 class="mt-4"><i class="mdi mdi-certificate text-warning"></i> Verification & Comments</h5>
        <table class="table table-borderless">
            <tr>
                <td><strong>Comments:</strong></td>
                <td>{{ $member->comments ?? 'None' }}</td>
            </tr>
        </table>
    </div>
</div>
