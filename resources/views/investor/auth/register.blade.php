@extends('investor.layouts.portal')

@section('title', 'Investor Registration')

@push('styles')
<style>
    .registration-page { margin: 0 auto; max-width: 1120px; }
    .registration-brand { align-items: center; display: flex; gap: 12px; margin-bottom: 24px; }
    .registration-brand img { border-radius: 5px; height: 44px; object-fit: cover; width: 44px; }
    .registration-brand strong { display: block; font-size: 16px; }
    .registration-brand span { color: var(--portal-muted); display: block; font-size: 12px; margin-top: 2px; }
    .registration-head { align-items: end; display: flex; gap: 24px; justify-content: space-between; margin-bottom: 24px; }
    .registration-head h1 { font-size: 30px; margin: 0 0 8px; }
    .registration-head p { color: var(--portal-muted); margin: 0; max-width: 720px; }
    .approval-label { background: #fff7e8; border: 1px solid #ebce95; border-radius: 5px; color: #7c470e; font-size: 13px; padding: 10px 12px; }
    .registration-form { background: #fff; border: 1px solid var(--portal-border); }
    .form-section { border-bottom: 1px solid var(--portal-border); padding: 26px; }
    .form-section:last-child { border-bottom: 0; }
    .form-section h2 { font-size: 17px; margin: 0 0 5px; }
    .form-section > p { color: var(--portal-muted); font-size: 13px; margin: 0 0 20px; }
    .form-grid { display: grid; gap: 18px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .field.full { grid-column: 1 / -1; }
    .field label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 7px; }
    .field input, .field select { background: #fff; border: 1px solid var(--portal-border); border-radius: 5px; font: inherit; min-height: 44px; padding: 10px 12px; width: 100%; }
    .field input.is-invalid, .field select.is-invalid { border-color: var(--portal-red); }
    .field input:focus, .field select:focus { border-color: var(--portal-green); box-shadow: 0 0 0 3px rgba(23,107,77,.1); outline: none; }
    .field small { color: var(--portal-muted); display: block; font-size: 11px; margin-top: 5px; }
    .field-error { color: var(--portal-red); display: block; font-size: 12px; margin-top: 5px; }
    .terms-row { align-items: flex-start; display: flex; gap: 10px; }
    .registration-actions { align-items: center; display: flex; gap: 18px; justify-content: space-between; }
    .registration-actions p { color: var(--portal-muted); font-size: 12px; margin: 0; max-width: 650px; }
    .register-submit { background: var(--portal-green); border: 0; border-radius: 5px; color: #fff; cursor: pointer; flex: 0 0 auto; font: inherit; font-weight: 700; padding: 13px 20px; }
    .register-submit:hover { background: var(--portal-green-dark); }
    .password-field { position: relative; }
    .password-field input { padding-right: 46px; }
    .password-toggle { background: transparent; border: 0; color: var(--portal-muted); cursor: pointer; padding: 9px; position: absolute; right: 5px; top: 30px; }
    .registration-login-link { color: var(--portal-muted); font-size: 13px; margin: 14px 0 0; text-align: center; }
    .registration-login-link a { font-weight: 700; }
    .status-check { background: #f8fbfc; border: 1px solid var(--portal-border); margin-bottom: 24px; padding: 18px 20px; }
    .status-check h2 { font-size: 16px; margin: 0 0 5px; }
    .status-check p { color: var(--portal-muted); font-size: 13px; margin: 0 0 14px; }
    .status-check-form { align-items: end; display: grid; gap: 12px; grid-template-columns: 1fr 1fr auto; }
    .status-check-form .field { margin: 0; }
    .status-check-button { background: var(--portal-blue); border: 0; border-radius: 5px; color: #fff; cursor: pointer; font: inherit; font-weight: 700; min-height: 44px; padding: 10px 15px; }
    .status-check-button:hover { background: #1e4d73; }
    .registration-status { border: 1px solid; border-radius: 5px; font-size: 13px; line-height: 1.5; margin-bottom: 18px; padding: 12px 14px; }
    .registration-status strong { display: block; margin-bottom: 3px; }
    .registration-status-success { background: #eaf7f0; border-color: #acd6be; color: #155c3f; }
    .registration-status-warning { background: #fff7e8; border-color: #ebce95; color: #7c470e; }
    .registration-status-error { background: #fff0f0; border-color: #e2b2b2; color: #8a2929; }
    .registration-status-info { background: #eef5f8; border-color: #b6d1df; color: #27485f; }
    @media (max-width: 720px) { .registration-head, .registration-actions { align-items: stretch; flex-direction: column; } .form-grid, .status-check-form { grid-template-columns: 1fr; } .field.full { grid-column: auto; } }
</style>
@endpush

@section('content')
<div class="registration-page">
<div class="registration-brand">
    <img src="{{ asset('admin/assets/images/ebims-logo.jpg') }}" alt="EBIMS">
    <div><strong>EBIMS Investor Portal</strong><span>Emuria Micro Finance Limited</span></div>
</div>

<div class="registration-head">
    <div>
        <h1>Investor registration</h1>
        <p>Create your investor profile and submit identity documents for review. Registration does not create an investment or guarantee approval.</p>
    </div>
    <div class="approval-label"><i class="mdi mdi-account-check-outline"></i> Super Admin approval required</div>
</div>

@if($errors->any())
    <div class="portal-alert portal-alert-error">
        <strong>Please correct the highlighted information.</strong>
        <div>{{ $errors->first() }}</div>
    </div>
@endif

@if(session('registration_status'))
    @php($registrationStatus = session('registration_status'))
    <div class="registration-status registration-status-{{ $registrationStatus['tone'] ?? 'info' }}" role="status">
        <strong>{{ $registrationStatus['title'] }}</strong>
        <span>{{ $registrationStatus['message'] }}</span>
        @if(($registrationStatus['status'] ?? null) === 'approved')
            <a href="{{ route('investor.login') }}">Go to investor login</a>
        @endif
    </div>
@endif

<section class="status-check" aria-labelledby="registration-status-heading">
    <h2 id="registration-status-heading">Already registered?</h2>
    <p>Check an existing investor registration with your email address or identification number before starting again.</p>
    <form class="status-check-form" method="POST" action="{{ route('investor.registration-status') }}">
        @csrf
        <div class="field">
            <label for="status_email">Email address</label>
            <input id="status_email" type="email" name="status_email" value="{{ old('status_email') }}" placeholder="you@example.com">
            @error('status_email')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <div class="field">
            <label for="status_IDnumber">Identification number</label>
            <input id="status_IDnumber" name="status_IDnumber" value="{{ old('status_IDnumber') }}" placeholder="National ID or passport number">
            @error('status_IDnumber')<span class="field-error">{{ $message }}</span>@enderror
        </div>
        <button class="status-check-button" type="submit"><i class="mdi mdi-magnify"></i> Check status</button>
    </form>
</section>

<form class="registration-form" method="POST" action="{{ route('investor.register.store') }}" enctype="multipart/form-data">
    @csrf
    <section class="form-section">
        <h2>Personal information</h2>
        <p>Enter your legal details exactly as they appear on your identification.</p>
        <div class="form-grid">
            <div class="field">
                <label for="title">Title</label>
                <select id="title" name="title" required>
                    <option value="">Select title</option>
                    @foreach([1 => 'Mr.', 2 => 'Mrs.', 3 => 'Ms.', 4 => 'Dr.', 5 => 'Prof.'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('title') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('title')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="dob">Date of birth</label>
                <input id="dob" type="date" name="dob" value="{{ old('dob') }}" required>
                @error('dob')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="fname">First name</label>
                <input id="fname" name="fname" value="{{ old('fname') }}" required>
                @error('fname')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="lname">Last name</label>
                <input id="lname" name="lname" value="{{ old('lname') }}" required>
                @error('lname')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">Select gender</option>
                    <option value="Male" @selected(old('gender') === 'Male')>Male</option>
                    <option value="Female" @selected(old('gender') === 'Female')>Female</option>
                </select>
                @error('gender')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="phone">Phone number</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel">
                @error('phone')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </section>

    <section class="form-section">
        <h2>Contact and location</h2>
        <p>This information supports verification and official communication.</p>
        <div class="form-grid">
            <div class="field full">
                <label for="email">Email address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field full">
                <label for="address">Physical address</label>
                <input id="address" name="address" value="{{ old('address') }}" required>
                @error('address')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="city">City or district</label>
                <input id="city" name="city" value="{{ old('city') }}" required>
                @error('city')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="country">Country</label>
                <select id="country" name="country" required>
                    <option value="">Select country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}" @selected(old('country') == $country->id)>{{ $country->name }}</option>
                    @endforeach
                </select>
                @error('country')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="zip">Postal code <span style="font-weight:400">(optional)</span></label>
                <input id="zip" name="zip" value="{{ old('zip') }}">
            </div>
        </div>
    </section>

    <section class="form-section">
        <h2>Identity verification</h2>
        <p>Documents are stored privately and reviewed only by authorized EBIMS staff.</p>
        <div class="form-grid">
            <div class="field">
                <label for="IDtype">Identification type</label>
                <select id="IDtype" name="IDtype" required>
                    <option value="">Select identification</option>
                    @foreach(['National ID', 'Passport', 'Driving Permit'] as $type)
                        <option value="{{ $type }}" @selected(old('IDtype') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
                @error('IDtype')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="IDnumber">Identification number</label>
                <input id="IDnumber" name="IDnumber" value="{{ old('IDnumber') }}" required>
                @error('IDnumber')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="id_document">Identification document</label>
                <input id="id_document" type="file" name="id_document" accept=".pdf,.jpg,.jpeg,.png" required>
                <small>PDF, JPG or PNG. Maximum 5 MB.</small>
                @error('id_document')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="profile_photo">Profile photograph</label>
                <input id="profile_photo" type="file" name="profile_photo" accept=".jpg,.jpeg,.png" required>
                <small>JPG or PNG. Maximum 2 MB.</small>
                @error('profile_photo')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </section>

    <section class="form-section">
        <h2>Portal security</h2>
        <p>Create a password for this investor portal. Do not reuse your banking password.</p>
        <div class="form-grid">
            <div class="field password-field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Show password"><i class="mdi mdi-eye-outline"></i></button>
                <small>At least 8 characters with upper/lowercase letters and a number.</small>
                @error('password')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="field password-field">
                <label for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                <button class="password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Show password"><i class="mdi mdi-eye-outline"></i></button>
            </div>
            <div class="field full terms-row">
                <input id="terms" type="checkbox" name="terms" value="1" @checked(old('terms')) required style="width:auto; min-height:auto; margin-top:3px">
                <label for="terms" style="font-weight:400">I confirm that the information is accurate and consent to verification by EBIMS. I understand that registration requires approval and does not itself create an investment.</label>
                @error('terms')<span class="field-error">{{ $message }}</span>@enderror
            </div>
        </div>
    </section>

    <section class="form-section registration-actions">
        <p>After submission, the Super Administrator will review your profile. You will only be able to sign in after approval.</p>
        <button class="register-submit" type="submit"><i class="mdi mdi-send-check-outline"></i> Submit for approval</button>
    </section>
</form>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const input = document.getElementById(button.dataset.passwordToggle);
            const icon = button.querySelector('i');
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
            icon.className = visible ? 'mdi mdi-eye-outline' : 'mdi mdi-eye-off-outline';
        });
    });
</script>
@endpush
