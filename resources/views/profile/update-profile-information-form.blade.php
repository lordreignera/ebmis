<form wire:submit="updateProfileInformation">
    <div class="form-body">
        <!-- Name -->
        <div class="form-group">
            <label for="name" class="form-label">
                <i class="mdi mdi-account text-primary"></i> Full Name
            </label>
            <input type="text" 
                   class="form-control @error('name') is-invalid @enderror" 
                   id="name" 
                   wire:model="state.name" 
                   required 
                   autocomplete="name"
                   placeholder="Enter your full name">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email" class="form-label">
                <i class="mdi mdi-email text-primary"></i> Email Address
            </label>
            <input type="email" 
                   class="form-control @error('email') is-invalid @enderror" 
                   id="email" 
                   wire:model="state.email" 
                   required 
                   autocomplete="username"
                   placeholder="Enter your email address">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
                <div class="alert alert-warning mt-2">
                    <i class="mdi mdi-alert"></i> Your email address is unverified.
                    <button type="button" class="btn btn-link btn-sm" wire:click.prevent="sendEmailVerification">
                        Click here to re-send the verification email.
                    </button>
                </div>

                @if ($this->verificationLinkSent)
                    <div class="alert alert-success mt-2">
                        <i class="mdi mdi-check-circle"></i> A new verification link has been sent to your email address.
                    </div>
                @endif
            @endif
        </div>

        <!-- Save Button -->
        <div class="form-group">
            @if (session('saved'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="mdi mdi-check-circle"></i> Profile information saved successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <button type="submit" 
                    class="btn btn-gradient-primary btn-lg" 
                    wire:loading.attr="disabled">
                <i class="mdi mdi-content-save"></i>
                <span wire:loading.remove>Save Changes</span>
                <span wire:loading>
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Saving...
                </span>
            </button>
        </div>
    </div>
</form>
